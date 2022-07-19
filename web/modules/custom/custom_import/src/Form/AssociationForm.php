<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AssociationForm extends FormBase {
  
  const SAVE_PATH = 'public://association/';
  const EXTERNAL_PATH = 'edhec_prod';
  
  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'association_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Associations'),
    ];
    
    $form['translations'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les traductions'),
      '#submit' => ['::translationSubmit'],
    ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }
  
  /**
   * Get Query
   */
  public function getQuery($translation) {
    
    // Prepare Directory
    $folder = self::SAVE_PATH;
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    // Older Content type : association
    // Newest Content Type : association
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('node', 'node');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    $query->condition('node.type', 'association');
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.bundle = 'association' AND field_image.entity_type = 'node'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_image.field_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Body
    $query->leftJoin('field_data_body', 'field_data_body', "field_data_body.entity_id = node.nid AND field_data_body.bundle = 'association' AND field_data_body.entity_type = 'node'");
    $query->fields('field_data_body', ['body_value', 'body_summary']);
    
    if($translation) {
      $query->where('node.nid != node.tnid');
      $query->condition('node.tnid', '0', '!=');
    } else {
      $orGroup = $query->orConditionGroup();
      $orGroup->condition('node.tnid', '0');
      $orGroup->where('node.nid = node.tnid');
      $query->condition($orGroup);
    }
    
    return $query->execute();
  }
  
  /**
   * Get Batch
   */
  public function getBatch($translation) {
    $results = $this->getQuery($translation);
    
    $items = [];
    foreach($results as $result) {
      $items[] = $result;
    }
    
    $operations = [];
    $chunk = array_chunk($items, 50);
    
    foreach($chunk as $ch) {
      $operations[] = [
        'Drupal\custom_import\Form\AssociationForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Associations...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\AssociationForm::finished',
      'init_message' => 'Import des Associations',
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch has encountered an error.'),
    ];
    
    batch_set($batch);
  }
  
  /**
   * {@inheritdoc}
   */
  public function translationSubmit(array &$form, FormStateInterface $form_state) {
    
    // Translation
    $translation = TRUE;
    
    // Batch
    $this->getBatch($translation);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // Node original translation
    $translation = FALSE;
    
    // Query
    $this->getBatch($translation);
  }
  
  public static function import($items, $translation, &$context) {
    $entityManager = \Drupal::entityTypeManager();
    $nodeStorage = $entityManager->getStorage('node');
    $service = \Drupal::service('custom_import.utility');
    
    foreach($items as $item) {
      self::addNode($item, $translation, $service, $nodeStorage);
    }
  }
  
  public static function addNode($item, $translation, $service, $nodeStorage) {

    $external_path = self::EXTERNAL_PATH;

    // Node / Translated Node
    $node = $service->prepareNode($item, 'association', $translation);
    
    // Body
    if(!empty($item->body_value)) {
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'association', $external_path);
      $node->body->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->body->setValue(NULL);
    }
    
    // Image
    $save_path = self::SAVE_PATH;
    $file = $service->getFile($item->uri, $item->filename, $save_path, $external_path);
    
    // Get Categories
    $tids = self::getCategories($item, $service);
    $node->field_categorie_association->setValue($tids);
    
    // Programme
    $tids = self::getProgrammes($item, $service);
    $node->field_programme->setValue($tids);
    
    // Localisation
    $tids = self::getLocalisation($item, $service);
    $node->field_campus->setValue($tids);
    
    // Image
    if($file) {
      $node->field_logo->setValue([
        'target_id' => $file->id(),
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
      ]);
    } else {
      $node->field_logo->setValue(NULL);
    }
    
    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function getLocalisation($item, $service) {
    // field_localisation
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('field_data_field_localisation', 'field_localisation');
    $query->fields('field_localisation', ['field_localisation_tid']);
    $query->condition('field_localisation.entity_id', $item->nid);
    $query->condition('field_localisation.entity_type', 'node');
    $query->condition('field_localisation.bundle', 'association');
    $results = $query->execute();
    
    $list = [];
    foreach($results as $result) {
      $list[] = $result->field_localisation_tid;
    }
    $tids = $service->getMultipleTerms($list, 'campus');
    
    return $tids;
  }
  
  public static function getProgrammes($item, $service) {
    // field_programme_de_r_f_rence
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('field_data_field_programme_de_r_f_rence', 'field_programme_de_r_f_rence');
    $query->fields('field_programme_de_r_f_rence', ['field_programme_de_r_f_rence_value']);
    $query->condition('field_programme_de_r_f_rence.entity_id', $item->nid);
    $query->condition('field_programme_de_r_f_rence.entity_type', 'node');
    $query->condition('field_programme_de_r_f_rence.bundle', 'association');
    $results = $query->execute();
    
    $list = [];
    foreach($results as $result) {
      $list[] = $result->field_programme_de_r_f_rence_value;
    }
    $tids = $service->getTerm($list, 'association_programme');

    return $tids;
  }
  
  public static function getCategories($item, $service) {
    // field_data_field_cat_gorie
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('field_data_field_cat_gorie', 'field_cat_gorie');
    $query->fields('field_cat_gorie', ['field_cat_gorie_tid']);
    $query->condition('field_cat_gorie.entity_id', $item->nid);
    $query->condition('field_cat_gorie.entity_type', 'node');
    $query->condition('field_cat_gorie.bundle', 'association');
    $results = $query->execute();
    
    $list = [];
    foreach($results as $result) {
      $list[] = $result->field_cat_gorie_tid;
    }
    $tids = $service->getTerm($list, 'association');

    return $tids;
  }
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }
  
}