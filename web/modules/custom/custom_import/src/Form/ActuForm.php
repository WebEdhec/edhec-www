<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActuForm extends FormBase {
  
  const SAVE_PATH = 'public://actualites/';
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;
  
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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->nodeStorage = $instance->entityTypeManager->getStorage('node');
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'actu_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Actualités'),
    ];
    
    $form['translations'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les traductions'),
      '#submit' => ['::translationSubmit'],
    ];
    
    // $form['delete'] = [
      // '#type' => 'submit',
      // '#value' => $this->t('Delete'),
      // '#submit' => ['::deleteSubmit'],
    // ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }
  
  public function deleteSubmit(array &$form, FormStateInterface $form_state) {
    $nodes = $this->nodeStorage
      ->loadByProperties([
        'type' => 'actualite'
      ]);
    
    $this->nodeStorage
      ->delete($nodes);
  }
  
  public function getQuery($translation) {
    
    // Prepare Directory
    $folder = self::SAVE_PATH;
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    // Older Content type : communiqu_de_presse
    // Newest Content Type : communique_de_presse
    // tx_emgoodpractices_domain_model_goodpractice
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('node', 'node');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    $query->condition('node.type', 'actualit_s');
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'actualit_s'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.entity_type = 'node' AND field_image.bundle = 'actualit_s'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_image.field_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Emetteur
    $query->leftJoin('field_data_field_emetteur', 'field_emetteur', "field_emetteur.entity_id = node.nid AND field_emetteur.entity_type = 'node' AND field_emetteur.bundle = 'actualit_s'");
    $query->fields('field_emetteur', ['field_emetteur_target_id']);
    
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
        'Drupal\custom_import\Form\ActuForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Actualités...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\ActuForm::finished',
      'init_message' => 'Import des Actualités',
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
    $termStorage = $entityManager->getStorage('taxonomy_term');
    $service = \Drupal::service('custom_import.utility');
    
    foreach($items as $item) {
      self::addNode($item, $translation, $service, $nodeStorage, $termStorage);
    }
  }
  
  public static function addNode($item, $translation, $service, $nodeStorage, $termStorage) {
    
    // Node / Translated Node
    $node = $service->prepareNode($item, 'actualite', $translation);
    
    // Body
    if(!empty($item->body_value)) {
      $chapo = $service->getChapo($item->body_value);
      $node->field_chapo->setValue([
        'value' => $chapo,
        'format' => 'basic_html',
      ]);
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'actualite');
      $node->body->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->body->setValue(NULL);
      $node->field_chapo->setValue(NULL);
    }
    
    // Image
    $save_path = self::SAVE_PATH;
    $media = $service->getMedia($item->uri, $item->filename, $save_path, 'image');
    
    if($media) {
      $node->field_header->setValue($media->id());
    } else {
      $node->field_header->setValue(NULL);
    }
    
    // Emetteur
    $emetteur = $service->getTerm($item->field_emetteur_target_id, 'destination');
    $node->field_emetteur->setValue($emetteur);
    
    // Cibles
    $cibles = self::getCibles($item, $service, $termStorage);
    $node->field_cible->setValue($cibles);

    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function getCibles($item, $service, $termStorage) {
    $connection = Database::getConnection('default', 'edhec_prod');
    
    // Cibles
    $query = $connection->select('field_data_field_cible', 'field_cible');
    $query->fields('field_cible', ['field_cible_tid']);
    $query->condition('field_cible.entity_type', 'node');
    $query->condition('field_cible.entity_id', $item->nid);
    $query->condition('field_cible.bundle', 'actualit_s');
    $results = $query->execute();
    
    $cibles = [];
    foreach($results as $result) {
      $cibles[] = $result->field_cible_tid;
    }
    $cibles = array_unique($cibles);
    
    // Destination
    $query = $connection->select('field_data_field_destination', 'field_destination');
    $query->fields('field_destination', ['field_destination_tid']);
    $query->condition('field_destination.entity_id', $item->nid);
    $query->condition('field_destination.entity_type', 'node');
    $query->condition('field_destination.bundle', 'actualit_s');
    $results = $query->execute();
    
    $destinations = [];
    foreach($results as $result) {
      $destinations[] = $result->field_destination_tid;
    }
    $destination = array_unique($destinations);
 
    $old_tids = array_merge($cibles, $destinations);

    if($old_tids) {
      $terms = $service->getTerm($old_tids, 'cible');
      return $terms;
    }
    return NULL;
  }
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }
}