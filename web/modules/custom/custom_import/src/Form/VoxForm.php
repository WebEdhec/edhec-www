<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\pathauto\PathautoState;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VoxForm extends FormBase {
  
  const SAVE_PATH = 'public://actualites-edhec-vox/';
  const EXTERNAL_PATH = 'edhec_prod';
  
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
    return 'vox_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Actualités Vox Edhec'),
    ];
    
    $form['translation'] = [
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
    $query->condition('node.type', 'article');
    
    // Image
    $query->leftJoin('field_data_field_image', 'field_image', "field_image.entity_id = node.nid AND field_image.entity_type = 'node' AND field_image.bundle = 'article'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_image.field_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'article'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Author
    $query->leftJoin('field_data_field_author', 'field_author', "field_author.entity_id = node.nid AND field_author.entity_type = 'node' AND field_author.bundle = 'article'");
    $query->fields('field_author', ['field_author_value']);
    
    // Video
    $query->leftJoin('field_data_field_videovox', 'field_videovox', "field_videovox.entity_id = node.nid AND field_videovox.entity_type = 'node' AND field_videovox.bundle = 'article'");
    $query->fields('field_videovox', ['field_videovox_video_url']);
    
    // $query->condition('node.nid', 62519);
    
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
        'Drupal\custom_import\Form\VoxForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des Actualités Edhec Vox...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\VoxForm::finished',
      'init_message' => 'Import des Actualités Edhec Vox',
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch has encountered an error.'),
    ];
    
    batch_set($batch);
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
  
  /**
   * {@inheritdoc}
   */
  public function translationSubmit(array &$form, FormStateInterface $form_state) {
    // Translation
    $translation = TRUE;
    
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
    
    $external_path = self::EXTERNAL_PATH;
    
    // Node / Translated Node
    $node = $service->prepareNode($item, 'actualite_edhecvox', $translation);
   
    // Body
    if(!empty($item->body_value)) {
      $chapo = $service->getChapo($item->body_value);
      $node->field_chapo->setValue([
        'value' => $chapo,
        'format' => 'basic_html',
      ]);
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'actualite_edhecvox', $external_path);
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
      $node->field_image->setValue($media->id());
    } else {
      $node->field_image->setValue(NULL);
    }
    
    // Auteur
    if(!empty($item->field_author_value)) {
      $text = $item->field_author_value;
      $new_text = $service->ckeditorImages($text, 'actualite_edhecvox', $external_path);
      $node->field_auteur_old_->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->field_auteur_old_->setValue(NULL);
    }
    
    // Video
    if(!empty($item->field_media)) {
      $node->field_media->setValue(1);
    } else {
      $node->field_media->setValue(0);
    }
		
		$alias = $node->path->alias;
		if(!empty($alias)) {
			if(substr($alias, 0, 10) == '/edhecvox/') {
				if($item->language == 'fr') {
					$new_alias = '/recherche-et-faculte/edhec-vox/' . substr($alias, 10);
				}
				if($item->language == 'en') {
					$new_alias = '/research-and-faculty/edhec-vox/' . substr($alias, 10);
				}
				$node->path->setValue([
					'alias' => $new_alias,
					'pathauto' => PathautoState::SKIP,
				]);
			}
		}
   
    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function finished($success, $results, $operations) {
    \Drupal::messenger()->addStatus('Terminé');
  }
  
}