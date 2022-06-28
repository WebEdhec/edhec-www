<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TheseForm extends FormBase {
  
  const SAVE_PATH = 'public://actualites-edhec-vox/';
  
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
    return 'these_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Thèses PHD'),
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
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
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
    $query->condition('node.type', 'thesis');
    
    // Date
    $query->leftJoin('field_data_field_date', 'field_date', "field_date.entity_id = node.nid AND field_date.entity_type = 'node' AND field_date.bundle = 'thesis'");
    $query->fields('field_date', ['field_date_value', 'field_date_value2']);
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'thesis'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Complement
    $query->leftJoin('field_data_field_complement_d_information', 'field_complement_d_information', "field_complement_d_information.entity_id = node.nid AND field_complement_d_information.entity_type = 'node' AND field_complement_d_information.bundle = 'thesis'");
    $query->fields('field_complement_d_information', ['field_complement_d_information_value']);
    
    // Type publication
    $query->leftJoin('field_data_field_type_de_publication', 'field_type_de_publication', "field_type_de_publication.entity_id = node.nid AND field_type_de_publication.entity_type = 'node' AND field_type_de_publication.bundle = 'thesis'");
    $query->fields('field_type_de_publication', ['field_type_de_publication_tid']);
    
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
        'Drupal\custom_import\Form\TheseForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Theses PHD...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\TheseForm::finished',
      'init_message' => 'Import des Theses PHD',
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch has encountered an error.'),
    ];
    
    batch_set($batch);
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
    $node = $service->prepareNode($item, 'these_phd', $translation);
    
    // Body
    if(!empty($item->body_value)) {
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'these_phd');
      $node->field_abstract2->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->field_abstract2->setValue(NULL);
    }
    
    // Date
    if(!empty($item->field_date_value)) {
      if(empty($item->field_date_value)) {
        $item->field_date_value2 = $item->field_date_value;
      }
      $date_start = new DrupalDateTime($item->field_date_value);
      $date_end = new DrupalDateTime($item->field_date_value2);
      $node->field_date_de_publication->setValue([
        'value' => $date_start->format('Y-m-d'),
        'end_value' => $date_end->format('Y-m-d'),
      ]);
    } else {
      $node->field_date_de_publication->setValue(NULL);
    }
    
    // Thesis
    $these = $service->getTerm($item->field_type_de_publication_tid, 'phd_these');
    $node->field_type->setValue($these);
    
    // Auteurs
    $auteurs = self::getAuteurs($item);
    $node->field_auteurs_externe->setValue($auteurs);
    
    // Documents
    $document = self::getDocument($item);
    $save_path = self::SAVE_PATH;
    $file = $service->getFile($document->uri, $document->filename, $save_path, $options = [], $external_path = 'edhec_prod');
    if($file) {
      $node->field_pdf->setValue($file->id());
    } else {
      $node->field_pdf->setValue(NULL);
    }
    
    // Comité
    if(!empty($item->field_complement_d_information_value)) {
      $text = $item->field_complement_d_information_value;
      $new_text = $service->ckeditorImages($text, 'these_phd');
      $node->field_comite_de_these->setValue([
        'value' => $new_text,
        'format' => 'basic_html',
      ]);
    } else {
      $node->field_comite_de_these->setValue(NULL);
    }

    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function getDocument($item) {
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('node', 'node');
    $query->condition('node.type', 'thesis');
    $query->join('field_data_field_documents', 'field_documents', "field_documents.entity_id AND field_documents.entity_type = 'node' AND field_documents.bundle = 'thesis'");
    $query->join('field_collection_item', 'field_collection_item', "field_collection_item.item_id = field_documents.field_documents_value AND field_collection_item.field_name = 'field_documents'");
    $query->join('field_data_field_fichier', 'field_fichier', "field_fichier.entity_id = field_collection_item.item_id AND field_fichier.entity_type = 'field_collection_item' AND field_fichier.bundle = 'field_documents'");
    $query->join('file_managed', 'file_managed', "file_managed.fid = field_fichier.field_fichier_fid");
    $query->fields('file_managed');
    $query->range(0, 1);
    $element = $query->execute()->fetchObject();
    
    return $element;
  }
  
  public static function getAuteurs($item) {
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('node', 'node');
    $query->condition('node.type', 'thesis');
    $query->condition('node.nid', $item->nid);
    $query->leftJoin('field_data_field_publication_auteur', 'field_publication_auteur', "field_publication_auteur.entity_id = node.nid AND field_publication_auteur.entity_type = 'node' AND field_publication_auteur.bundle = 'thesis'");
    $query->leftJoin('field_collection_item', 'field_collection_item', "field_collection_item.item_id = field_publication_auteur.field_publication_auteur_value AND field_collection_item.field_name = 'field_publication_auteur'");
    $query->leftJoin('field_data_field_nom', 'field_nom', "field_nom.entity_id = field_collection_item.item_id AND field_nom.entity_type = 'field_collection_item' AND field_nom.bundle = 'field_publication_auteur'");
    $query->fields('field_nom', ['field_nom_value']);
    
    $results = $query->execute();
    
    $noms = [];
    foreach($results as $result) {
      $noms[] = $result->field_nom_value;
    }
    
    return implode(', ', $noms);
  }
  
}