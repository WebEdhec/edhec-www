<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StartUpForm extends FormBase {
  
  const SAVE_PATH = 'public://start-up/';
  const EXTERNAL_PATH = 'entrepreneur';
  
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
    return 'start_up_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Importer les Start Up'),
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
  
  public function getQuery($translation) {
    
    // Prepare Directory
    $folder = self::SAVE_PATH;
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    // personne
    $connection = Database::getConnection('default', 'eyeedhec');
    
    $query = $connection->select('node', 'node');
    $query->condition('node.type', 'personne');
    
    $query->fields('node', ['nid', 'tnid', 'title', 'status', 'language', 'created', 'changed']);
    
    // Image
    $query->leftJoin('field_data_field_team_image', 'field_team_image', "field_team_image.entity_id = node.nid AND field_team_image.entity_type = 'node' AND field_team_image.bundle = 'personne'");
    $query->leftJoin('file_managed', 'file_managed', "file_managed.fid = field_team_image.field_team_image_fid");
    $query->fields('file_managed', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Body
    $query->leftJoin('field_data_body', 'body', "body.entity_id = node.nid AND body.entity_type = 'node' AND body.bundle = 'personne'");
    $query->fields('body', ['body_value', 'body_summary']);
    
    // Site Web
    $query->leftJoin('field_data_field_siteweb', 'field_siteweb', "field_siteweb.entity_id = node.nid AND field_siteweb.entity_type = 'node' AND field_siteweb.bundle = 'personne'");
    $query->fields('field_siteweb', ['field_siteweb_url', 'field_siteweb_title']);
    
    // Facebook
    $query->leftJoin('field_data_field_facebook', 'field_facebook', "field_facebook.entity_id = node.nid AND field_facebook.entity_type = 'node' AND field_facebook.bundle = 'personne'");
    $query->fields('field_facebook', ['field_facebook_url', 'field_facebook_title']);
    
    // Twitter
    $query->leftJoin('field_data_field_twitter', 'field_twitter', "field_twitter.entity_id = node.nid AND field_twitter.entity_type = 'node' AND field_twitter.bundle = 'personne'");
    $query->fields('field_twitter', ['field_twitter_url', 'field_twitter_title']);
    
    // Linkedin
    $query->leftJoin('field_data_field_linkendin', 'field_linkendin', "field_linkendin.entity_id = node.nid AND field_linkendin.entity_type = 'node' AND field_linkendin.bundle = 'personne'");
    $query->fields('field_linkendin', ['field_linkendin_url', 'field_linkendin_title']);
    
    // Instagram
    $query->leftJoin('field_data_field_instagram', 'field_instagram', "field_instagram.entity_id = node.nid AND field_instagram.entity_type = 'node' AND field_instagram.bundle = 'personne'");
    $query->fields('field_instagram', ['field_instagram_url', 'field_instagram_title']);
    
    // Logo Entreprise
    $query->leftJoin('field_data_field_logo_entreprise', 'field_logo_entreprise', "field_logo_entreprise.entity_id = node.nid AND field_logo_entreprise.entity_type = 'node' AND field_logo_entreprise.bundle = 'personne'");
    $query->leftJoin('file_managed', 'file_managed_logo', "file_managed_logo.fid = field_logo_entreprise.field_logo_entreprise_fid");
    $query->fields('file_managed_logo', ['filename', 'uri', 'filemime', 'status', 'type', 'timestamp']);
    
    // Promo
    $query->leftJoin('field_data_field_promo_edhec', 'field_promo_edhec', "field_promo_edhec.entity_id = node.nid AND field_promo_edhec.entity_type = 'node' AND field_promo_edhec.bundle = 'personne'");
    $query->fields('field_promo_edhec', ['field_promo_edhec_value']);
    
    // Incubateur
    $query->leftJoin('field_data_field_bloc_incubateur', 'field_bloc_incubateur', "field_bloc_incubateur.entity_id = node.nid AND field_bloc_incubateur.entity_type = 'node' AND field_bloc_incubateur.bundle = 'personne'");
    $query->fields('field_bloc_incubateur', ['field_bloc_incubateur_value']);
    
    // Lien interview
    $query->leftJoin('field_data_field_lien_interview', 'field_lien_interview', "field_lien_interview.entity_id = node.nid AND field_lien_interview.entity_type = 'node' AND field_lien_interview.bundle = 'personne'");
    $query->fields('field_lien_interview', ['field_lien_interview_url', 'field_lien_interview_title']);
    
    // Incubateur
    $query->leftJoin('field_data_field_incubateur', 'field_incubateur', "field_incubateur.entity_id = node.nid AND field_incubateur.entity_type = 'node' AND field_incubateur.bundle = 'personne'");
    $query->fields('field_incubateur', ['field_incubateur_tid']);
    
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
        'Drupal\custom_import\Form\StartUpForm::import',
        [$ch, $translation],
      ];
    }
    
    // Batch
    $batch = [
      'title' => $this->t('Importation des StartUp...'),
      'operations' => $operations,
      'finished' => 'Drupal\custom_import\StartUpForm::finished',
      'init_message' => 'Import des StartUp',
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
    
    // Batch
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
    $node = $service->prepareNode($item, 'startup', $translation, 'eyeedhec');
    
    // Field LOGO
    $save_path = self::SAVE_PATH;
    $file = $service->getFile($item->uri, $item->filename, $save_path, $external_path);
    
    if($file) {
      $node->field_logo->setValue([
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
        'target_id' => $file->id(),
      ]);
    } else {
      $node->field_logo->setValue(NULL);
    }
    
    // Field PHOTO
    $file = $service->getFile($item->file_managed_logo_uri, $item->file_managed_logo_filename, $save_path, $external_path);
    
    if($file) {
      $node->field_photo->setValue([
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
        'target_id' => $file->id(),
      ]);
    } else {
      $node->field_photo->setValue(NULL);
    }
    
    // Site Web
    $website = $item->field_siteweb_url;
    if(!empty($website)) {
      $website = $service->checkUrl($website);
      $node->field_site_web->setValue([
        'title' => $item->field_siteweb_title,
        'uri' => $website,
      ]);
    } else {
      $node->field_site_web->setValue(NULL);
    }
    
    // Twitter
    $twitter = $item->field_twitter_url;
    if(!empty($twitter)) {
      $twitter = $service->checkUrl($twitter);
      $node->field_twitter->setValue([
        'title' => $item->field_twitter_title,
        'uri' => $twitter,
      ]);
    } else {
      $node->field_twitter->setValue(NULL);
    }
    
    // Facebook
    $facebook = $item->field_facebook_url;
    if(!empty($facebook)) {
      $facebook = $service->checkUrl($facebook);
      $node->field_facebook->setValue([
        'title' => $item->field_facebook_title,
        'uri' => $facebook,
      ]);
    } else {
      $node->field_facebook->setValue(NULL);
    }
    
    // Linkedin
    $linkedin = $item->field_linkendin_url;
    if(!empty($linkedin)) {
      $linkedin = $service->checkUrl($linkedin);
      $node->field_linkedin->setValue([
        'title' => $item->field_linkendin_title,
        'uri' => $linkedin,
      ]);
    } else {
      $node->field_linkedin->setValue(NULL);
    }
    
    // Instagram
    $instagram = $item->field_instagram_url;
    if(!empty($instagram)) {
      $instagram = $service->checkUrl($instagram);
      $node->field_instagram->setValue([
        'title' => $item->field_instagram_title,
        'uri' => $instagram,
      ]);
    } else {
      $node->field_instagram->setValue(NULL);
    }
    
    // Promo
    $node->field_promo->setValue($item->field_promo_edhec_value);
    
    // Bloc Incubateur
    if(!empty($item->field_bloc_incubateur_value)) {
      $text = $item->field_bloc_incubateur_value;
      $new_text = $service->ckeditorImages($text, 'startup', $external_path);
      $node->field_chapo->setValue([
        'value' => $new_text,
        'format' => 'basic_html',
      ]);
    } else {
      $node->field_chapo->setValue(NULL);
    }
    
    // Lien interview
    $interview = $item->field_lien_interview_url;
    if(!empty($item->field_lien_interview_url)) {
      $interview = $service->checkUrl($interview);
      $node->field_interview->setValue([
        'title' => $item->field_lien_interview_title,
        'uri' => $interview,
      ]);
    } else {
      $node->field_interview->setValue(NULL);
    }
    
    // Incubateur
    list($incubateur, $profil) = self::getIncubateurs($item, $service);
    $node->field_lieu_d_incubation->setValue($incubateur);
    $node->field_profil->setValue($profil);

    // Description
    if(!empty($item->body_value)) {
      $text = $item->body_value;
      $new_text = $service->ckeditorImages($text, 'startup', $external_path);
      $node->body->setValue([
        'value' => $new_text,
        'format' => 'full_html',
      ]);
    } else {
      $node->body->setValue(NULL);
    }

    $node->setChangedTime($item->changed);
    $node->save();
  }
  
  public static function getIncubateurs($item, $service) {
    $incubateur = $item->field_incubateur_tid;
    $tid = NULL;
    $profil = NULL;
    if(!empty($incubateur)) {
      $tids = $service->getTerm($incubateur, 'lieu_incubation');
      
      // si 232, 233, 231, 234,=> "incubé", si 235 => Alumni
      if($tids) {
        $tid = reset($tids);
        if(in_array($incubateur, ['232', '233', '231', '234'])) {
          $profil = '84';
        }
      }
      
      if(!empty($incubateur)) {
        if($incubateur == '235') {
          $profil = '85';
        }
      }

      return [$tid, $profil];
    }
    return NULL;
  }

}