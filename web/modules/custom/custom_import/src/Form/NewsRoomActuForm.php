<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NewsRoomActuForm extends FormBase {
  
  const SAVE_PATH = 'public://actualites/';
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
    return 'news_room_actu_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Newsroom actualités'),
    ];
    
    $form['translations'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les traductions'),
      '#submit' => ['::translationSubmit'],
    ];
    
    $form['delete_news'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete News newsroom'),
      '#submit' => ['::deleteNewsSubmit'],
    ];
      
    return $form;
  }
  
  public function deleteNewsSubmit(array &$form, FormStateInterface $form_state) {
    $query = $this->database->select('node_field_data', 'node');
    $query->fields('node', ['nid']);
    $query->condition('node.type', 'actualite');
    $query->join('node__field_old_id', 'field_old_id', "field_old_id.entity_id = node.nid AND field_old_id.bundle = 'actualite'");
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
  
  public function getQuery($translation) {
    // Prepare Directory
    $folder = self::SAVE_PATH;
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    // Older Content type : communiqu_de_presse
    // Newest Content Type : communique_de_presse
    // tx_emgoodpractices_domain_model_goodpractice
    $connection = Database::getConnection('default', 'newsroomedhec');
    
    $query = $connection->select('node', 'node');
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    $query->condition('node.type', 'news');
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'news'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Sous Titre
    $query->leftJoin('field_data_field_sous_titre', 'field_sous_titre', "field_sous_titre.entity_id = node.nid AND field_sous_titre.bundle = 'news' AND field_sous_titre.entity_type = 'node'");
    $query->fields('field_sous_titre', ['field_sous_titre_value']);
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.bundle = 'news' AND field_image.entity_type = 'node'");
    $query->leftJoin('file_managed', 'file_managed', 'file_managed.fid = field_image.field_image_fid');
    $query->fields('file_managed', ['filename', 'uri']);
    
    // Sites
    $query->leftJoin('field_data_field_site_edhec', 'field_site_edhec', "field_site_edhec.entity_id = node.nid AND field_site_edhec.bundle = 'news' AND field_site_edhec.entity_type = 'node'");
    $query->leftJoin('field_data_field_site_edhec_to', 'field_site_edhec_to', "field_site_edhec_to.entity_id = node.nid AND field_site_edhec_to.bundle = 'news' AND field_site_edhec_to.entity_type = 'node'");
    $query->fields('field_site_edhec', ['field_site_edhec_tid']);
    
    
    $db_or = $query->orConditionGroup();
    $db_or->isNull('field_site_edhec.field_site_edhec_tid');
    $db_or->condition('field_site_edhec.field_site_edhec_tid', 11, '!=');
    $query->condition($db_or);
    
    $sub_query_2 = $connection->select('field_data_field_site_edhec_to', 'field_site_edhec_to');
    $sub_query_2->fields('field_site_edhec_to', ['entity_id']);
    $sub_query_2->condition('field_site_edhec_to.entity_type', 'node');
    $sub_query_2->condition('field_site_edhec_to.bundle', 'news');
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
        'Drupal\custom_import\Form\NewsRoomActuForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des NewsRoom Actualités...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\NewsRoomActuForm::finished',
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
    
    // Get Batch
    $this->getBatch($translation);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Translation
    $translation = FALSE;
    
    // Get Batch
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
  
  public static function checkEduNewsroom($item, $nodeStorage) {
    $nids = $nodeStorage
      ->getQuery()
      ->condition('type', 'actualite')
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
    
    $item->nid_newsroom = $item->nid;
    $item->nid = $item->nid . '-newsroom';
    
    $external_path = self::EXTERNAL_PATH;
    
    // Node / Translated Node
    $node = $service->prepareNode($item, 'actualite', $translation, 'newsroomedhec');
    
    // Body
    if(!empty($item->body_value)) {
      $chapo = $service->getChapo($item->body_value);
      $node->field_chapo->setValue([
        'value' => $chapo,
        'format' => 'basic_html',
      ]);
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'actualite', $external_path);
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
    $media = $service->getMedia($item->uri, $item->filename, $save_path, 'image', $external_path);
    
    if($media) {
      $node->field_header->setValue($media->id());
    } else {
      $node->field_header->setValue(NULL);
    }
    
     // Cibles
    $cibles = self::getCibles($item, $service, $termStorage);
    $node->field_cible->setValue($cibles);
    
    // Emetteur
    $emetteur = $service->getTerm($item->field_site_edhec_tid, 'destination', TRUE);
    $node->field_emetteur->setValue($emetteur);

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
    $query->condition('field_cible.bundle', 'news');
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
    $query->condition('field_site_edhec_to.bundle', 'news');
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
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }
  
}