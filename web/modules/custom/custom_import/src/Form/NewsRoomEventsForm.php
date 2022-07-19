<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NewsRoomEventsForm extends FormBase {
  
  const SAVE_PATH = 'public://evenements/';
  const EXTERNAL_PATH = 'newsroom';
  
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
    $instance->database = $container->get('database');
    return $instance;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'news_room_events_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Newsroom Events'),
    ];
    
    $form['translations'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les traductions'),
      '#submit' => ['::translationSubmit'],
    ];
    
    $form['delete_events'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Events newsroom'),
      '#submit' => ['::deleteEventsSubmit'],
    ];
    
    return $form;
  }
  
  public function deleteEventsSubmit(array &$form, FormStateInterface $form_state) {
    $query = $this->database->select('node_field_data', 'node');
    $query->fields('node', ['nid']);
    $query->condition('node.type', 'evenement');
    $query->join('node__field_old_id', 'field_old_id', "field_old_id.entity_id = node.nid AND field_old_id.bundle = 'evenement'");
    $query->condition('field_old_id.field_old_id_value', '%' . $this->database->escapeLike('newsroom') . '%', 'LIKE');
    $results = $query->execute();
    
    $nids = [];
    foreach($results as $result) {
      $nids[] = $result->nid;
    }
    
    $nodes = $this->nodeStorage->loadMultiple($nids);
    $this->nodeStorage->delete($nodes);
    $this->messenger()->addStatus($this->t('Terminé'));
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
    
    $connection = Database::getConnection('default', 'newsroomedhec');
    
    $query = $connection->select('node', 'node');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    $query->condition('node.type', 'event');
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'event'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.entity_type = 'node' AND field_image.bundle = 'event'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_image.field_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime']);
    
    // CTA
    $query->leftJoin('field_data_field_lien', 'field_lien', "field_lien.entity_id = node.nid AND field_lien.entity_type = 'node' AND field_lien.bundle = 'event'");
    $query->fields('field_lien', ['field_lien_value']);
    
    // Date
    $query->leftJoin('field_data_field_date', 'field_date', "field_date.entity_id = node.nid AND field_date.entity_type = 'node' AND field_date.bundle = 'event'");
    $query->fields('field_date', ['field_date_value', 'field_date_value2']);
    
    // Heure
    $query->leftJoin('field_data_field_hour', 'field_hour', "field_hour.entity_id = node.nid AND field_hour.entity_type = 'node' AND field_hour.bundle = 'event'");
    $query->fields('field_hour', ['field_hour_value']);
    
    // Lieu
    $query->leftJoin('field_data_field_place_event', 'field_place_event', "field_place_event.entity_id = node.nid AND field_place_event.entity_type = 'node' AND field_place_event.bundle = 'event'");
    $query->fields('field_place_event', ['field_place_event_value']);
    
    // Type evenement
    $query->leftJoin('field_data_field_category_event', 'field_category_event', "field_category_event.entity_id = node.nid AND field_category_event.entity_type = 'node' AND field_category_event.bundle = 'event'");
    $query->fields('field_category_event', ['field_category_event_tid']);

    // Sites
    $query->leftJoin('field_data_field_site_edhec', 'field_site_edhec', "field_site_edhec.entity_id = node.nid AND field_site_edhec.bundle = 'event' AND field_site_edhec.entity_type = 'node'");
    $query->leftJoin('field_data_field_site_edhec_to', 'field_site_edhec_to', "field_site_edhec_to.entity_id = node.nid AND field_site_edhec_to.bundle = 'event' AND field_site_edhec_to.entity_type = 'node'");
    $query->fields('field_site_edhec', ['field_site_edhec_tid']);
    
    $db_or = $query->orConditionGroup();
    $db_or->isNull('field_site_edhec.field_site_edhec_tid');
    $db_or->condition('field_site_edhec.field_site_edhec_tid', 11, '!=');
    $query->condition($db_or);
    
    $sub_query_2 = $connection->select('field_data_field_site_edhec_to', 'field_site_edhec_to');
    $sub_query_2->fields('field_site_edhec_to', ['entity_id']);
    $sub_query_2->condition('field_site_edhec_to.entity_type', 'node');
    $sub_query_2->condition('field_site_edhec_to.bundle', 'event');
    $sub_query_2->where('field_site_edhec_to.entity_id = node.nid');
    $sub_query_2->condition('field_site_edhec_to.field_site_edhec_to_tid', 11);
    
    $query->condition('node.nid', $sub_query_2, 'NOT IN');
    
    $query->groupBy('node.nid');

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
        'Drupal\custom_import\Form\NewsRoomEventsForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des NewsRoom Evenements...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\NewsRoomEventsForm::finished',
      'init_message' => 'Import des NewsRoom Evenements',
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
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }
  
  public static function checkEduNewsroom($item, $nodeStorage) {
    $nids = $nodeStorage
      ->getQuery()
      ->condition('type', 'evenement')
      ->condition('field_od_id_ws', $item->nid)
      ->count()
      ->execute();

    return $nids ? TRUE : FALSE;
  }
  
  public static function addNode($item, $translation, $service, $nodeStorage, $termStorage) {
    
    // Check if event already importer from edhec edu
    $check_edu = self::checkEduNewsroom($item, $nodeStorage);
    if($check_edu) {
      return;
    }
    
    $external_path = self::EXTERNAL_PATH;
    
    $item->nid_newsroom = $item->nid;
    $item->nid = $item->nid . '-newsroom';
    
    // Node / Translated Node
    $node = $service->prepareNode($item, 'evenement', $translation, 'newsroomedhec');
    
    // Body
    if(!empty($item->body_value)) {
      $chapo = $service->getChapo($item->body_value);
      $node->field_chapo->setValue([
        'value' => $chapo,
        'format' => 'basic_html',
      ]);
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'evenement', $external_path);
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
      if(empty($item->field_date_value2)) {
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

    // Lieu
    $lieu = $service->replaceBr($item->field_place_event_value);
    $node->field_lieu->setValue($lieu);
    
    // Heure
    $date_complement = $service->replaceBr($item->field_hour_value);
    $node->field_date_complement->setValue($date_complement);
    
    // CTA
    $cta = $item->field_lien_value;
    if(!empty($cta) && $cta != '|') {
      $explode = explode('|', $cta);
      $link_uri = $explode[1];
      $link_title = $explode[0];
      if(!empty($link_uri)) {
        $link_uri = $service->checkUrl($link_uri);
        $node->field_s_inscrire->setValue([
          'title' => $link_title,
          'uri' => $link_uri,
        ]);
      } else {
        $node->field_s_inscrire->setValue(NULL);
      }
    } else {
      $node->field_s_inscrire->setValue(NULL);
    }
    
    // Image
    $save_path = self::SAVE_PATH;
    $media = $service->getMedia($item->uri, $item->filename, $save_path, 'image', $external_path);
    if($media) {
      $node->field_image->setValue($media->id());
    } else {
      $node->field_image->setValue(NULL);
    }
    
     // Cibles
    $cibles = self::getCibles($item, $service, $termStorage);
    $node->field_cible->setValue($cibles);
    
    // Event Categorie
    $categorie = $service->getTerm($item->field_category_event_tid, 'evenement_format', TRUE);
    $node->field_format->setValue($categorie);
    
    $node->field_langue->setValue(89);
    
    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function getCibles($item, $service, $termStorage) {
    $connection = Database::getConnection('default', 'newsroomedhec');
    
    // Cibles
    $query = $connection->select('field_data_field_cible', 'field_cible');
    $query->fields('field_cible', ['field_cible_tid']);
    $query->condition('field_cible.entity_type', 'node');
    $query->condition('field_cible.entity_id', $item->nid_newsroom);
    $query->condition('field_cible.bundle', 'event');
    $results = $query->execute();
    
    $cibles = [];
    foreach($results as $result) {
      $cibles[] = $result->field_cible_tid;
    }
    $cibles = array_unique($cibles);
    
    // Destination
    $query = $connection->select('field_data_field_site_edhec_to', 'field_site_edhec_to');
    $query->fields('field_site_edhec_to', ['field_site_edhec_to_tid']);
    $query->condition('field_site_edhec_to.entity_id', $item->nid_newsroom);
    $query->condition('field_site_edhec_to.entity_type', 'node');
    $query->condition('field_site_edhec_to.bundle', 'event');
    $results = $query->execute();
    
    $destinations = [];
    foreach($results as $result) {
      $destinations[] = $result->field_site_edhec_to_tid;
    }
    $destination = array_unique($destinations);

    $old_tids = array_merge($cibles, $destinations);

    if($old_tids) {
      $terms = $service->getTerm($old_tids, 'cible', TRUE);
      return $terms;
    }
    return NULL;
  }
}