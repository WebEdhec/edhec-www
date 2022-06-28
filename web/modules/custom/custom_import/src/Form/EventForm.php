<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventForm extends FormBase {
  
  const SAVE_PATH = 'public://evenements/';
  
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
    return 'event_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Evenements'),
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
    
    // Older Content type : communiqu_de_presse
    // Newest Content Type : communique_de_presse
    // tx_emgoodpractices_domain_model_goodpractice
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('node', 'node');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    $query->condition('node.type', 'event');
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.entity_type = 'node' AND field_image.bundle = 'event'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_image.field_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'event'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // CTA
    $query->leftJoin('field_data_field_cta', 'field_cta', "field_cta.entity_id = node.nid AND field_cta.entity_type = 'node' AND field_cta.bundle = 'event'");
    $query->fields('field_cta', ['field_cta_url', 'field_cta_title']);
    
    // Date
    $query->leftJoin('field_data_field_date', 'field_date', "field_date.entity_id = node.nid AND field_date.entity_type = 'node' AND field_date.bundle = 'event'");
    $query->fields('field_date', ['field_date_value', 'field_date_value2']);
    
    // Heure
    $query->leftJoin('field_data_field_heure', 'field_heure', "field_heure.entity_id = node.nid AND field_heure.entity_type = 'node' AND field_heure.bundle = 'event'");
    $query->fields('field_heure', ['field_heure_value']);
    
    // Lieu
    $query->leftJoin('field_data_field_lieu', 'field_lieu', "field_lieu.entity_id = node.nid AND field_lieu.entity_type = 'node' AND field_lieu.bundle = 'event'");
    $query->fields('field_lieu', ['field_lieu_value']);
    
    // Categorie
    $query->leftJoin('field_data_field_type_event', 'field_type_event', "field_type_event.entity_id = node.nid AND field_type_event.entity_type = 'node' AND field_type_event.bundle = 'event'");
    $query->fields('field_type_event', ['field_type_event_tid']);
    
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
        'Drupal\custom_import\Form\EventForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Evenements...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\EventForm::finished',
      'init_message' => 'Import des Evenements',
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
    // Translation
    $translation = FALSE;
    
    // Batch
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
    $node = $service->prepareNode($item, 'evenement', $translation);
    
    // Body
    if(!empty($item->body_value)) {
      $chapo = $service->getChapo($item->body_value);
      $node->field_chapo->setValue([
        'value' => $chapo,
        'format' => 'basic_html',
      ]);
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'evenement');
      $node->body->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->body->setValue(NULL);
      $node->field_chapo->setValue(NULL);
    }
    
    // Date
    if(!empty($item->field_date_value)) {
      if(empty($item->field_date_value)) {
        $item->field_date_value2 = $item->field_date_value;
      }
      $date_start = new DrupalDateTime($item->field_date_value);
      $date_end = new DrupalDateTime($item->field_date_value2);
      $node->field_date->setValue([
        'value' => $date_start->format('Y-m-d'),
        'end_value' => $date_end->format('Y-m-d'),
      ]);
    } else {
      $node->field_date->setValue(NULL);
    }
    
    // Heure
    $date_completement = $service->replaceBr($item->field_heure_value);
    $node->field_date_complement->setValue($date_completement);
    
    // Lieu
    $lieu = $service->replaceBr($item->field_lieu_value);
    $node->field_lieu->setValue($lieu);
    
    // CTA
    $link = $item->field_cta_url;
    if(!empty($link)) {
      $link = $service->checkUrl($link);
      $node->field_s_inscrire->setValue([
        'title' => $item->field_cta_title,
        'uri' => $link,
      ]);
    } else {
      $node->field_s_inscrire->setValue(NULL);
    }
    
    // Event Categorie
    $categorie = $service->getTerm($item->field_type_event_tid, 'evenement_format');
    $node->field_format->setValue($categorie);
    
    // Cibles
    $cibles = self::getCibles($item, $service, $termStorage);
    $node->field_cible->setValue($cibles);
    
    // Image
    $save_path = self::SAVE_PATH;
    $media = $service->getMedia($item->uri, $item->filename, $save_path, 'image');
    if($media) {
      $node->field_image->setValue($media->id());
    } else {
      $node->field_image->setValue(NULL);
    }
    
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
    $query->condition('field_cible.bundle', 'event');
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
    $query->condition('field_destination.bundle', 'event');
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