<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommuniquePresseForm extends FormBase {

  const SAVE_PATH = 'public://communiques-presse/';
  const EXTERNAL_PATH = 'edhec_prod';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    return 'communique_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Communiqués de Presse'),
    ];
    
    $form['import_translation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Traductions'),
      '#submit' => ['::translationSubmit'],
    ];
    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }
  
  public function getQuery($translation) {
    
    // Prepare Directory
    $folder = self::SAVE_PATH;
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    // Older Content type : communiqu_de_presse
    // Newest Content Type : communique_de_presse
    // tx_emgoodpractices_domain_model_goodpractice
    $connection = Database::getConnection('default', 'edhec_prod');

    // Node
    $query = $connection->select('node', 'node');
    $query->condition('node.type', 'communiqu_de_presse');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    
    // Chapo
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'communiqu_de_presse'");
    $query->fields('body', ['body_value']);
    
    // PDF
    $query->leftJoin('field_data_field_pdf', 'field_pdf', "field_pdf.entity_id = node.nid AND field_pdf.entity_type = 'node' AND field_pdf.bundle = 'communiqu_de_presse'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_pdf.field_pdf_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);

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
        'Drupal\custom_import\Form\CommuniquePresseForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Traductions de Communiqués de Presse...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\CommuniquePresseForm::finished',
      'init_message' => 'Import des Traductions de Communiqués de Presse',
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
    $node = $service->prepareNode($item, 'communique_de_presse', $translation);
    
    // Body
    if(!empty($item->body_value)) {
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'communique_de_presse', $external_path);
      $node->field_chapo_presse->setValue([
        'value' => $new_text,
        'format' => 'basic_html',
      ]);
    } else {
      $node->field_chapo_presse->setValue(NULL);
    }
    
    // PDF
    $save_path = self::SAVE_PATH;
    $file = $service->getFile($item->uri, $item->filename, $save_path, $external_path);
    
    if($file) {
      $node->field_pdf->setValue($file->id());
    } else {
      $node->field_pdf->setValue(NULL);
    }

    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }

}