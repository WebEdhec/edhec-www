<?php

namespace Drupal\custom_import\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Views;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Xss;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\pathauto\PathautoState;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Component\Utility\Unicode;

class Utility {
  
	use StringTranslationTrait;
  use MessengerTrait;
  use LoggerChannelTrait;
	
	protected $entityTypeManager;
	protected $tempStore;
	protected $database;
	protected $logger;
	protected $currentUser;
  protected $userStorage;
	protected $file_system;
  protected $dateFormat;
  protected $routeMatch;
  protected $renderer;
  protected $token;
  protected $apiConfig = [];
  
  const EDHEC_EDU_PUBLIC_PATH = 'https://www.edhec.edu/sites/www.edhec-portail.pprod.net/files/';
  const EDHEC_ENTREPENEUR_PUBLIC_PATH = 'https://entrepreneurs.edhec.edu/sites/entrepreneurs/files/';
  const SAVE_PATH = 'public://communiques-presse/';
  
  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;
  
	public function __construct(
		EntityTypeManagerInterface $entity_manager,
		Connection $database,
		LoggerChannelFactoryInterface $logger,
		PrivateTempStoreFactory $tempstore,
		AccountProxy $current_user,
		FileSystem $file_system,
    DateFormatter $date_format,
    CurrentRouteMatch $route_match,
		ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
		FormBuilderInterface $form_builder,
		Client $http_client,
    CountryRepositoryInterface $country_repository,
    FileRepositoryInterface $file_repository
	) {
		$this->entityTypeManager = $entity_manager;
		$this->database = $database;
		$this->logger = $logger;
		$this->tempStore = $tempstore;
		$this->currentUser = $current_user;
		$this->fileSystem = $file_system;
    $this->dateFormat = $date_format;
    $this->routeMatch = $route_match;
		$this->termStorage = $this->entityTypeManager
			->getStorage('taxonomy_term');
		$this->nodeStorage = $this->entityTypeManager
			->getStorage('node');
		$this->fileStorage = $this->entityTypeManager
			->getStorage('file');
    $this->userStorage = $this->entityTypeManager
      ->getStorage('user');
    $this->mediaStorage = $this->entityTypeManager
      ->getStorage('media');
    $this->aliasStorage = $this->entityTypeManager
      ->getStorage('path_alias');
		$this->configFactory = $config_factory;
    $this->renderer = $renderer;
		$this->formBuilder = $form_builder;
    $this->httpClient = $http_client;
    $this->countryRepository = $country_repository;
    $this->fileRepository = $file_repository;
	}
  
  public static function getTarget($uri) {

    [, $target] = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }
  
  /**
   * Save trace
   */
  public function addFileTrace($uri, $fid) {
    $this->database->insert('file_import')
      ->fields([
        'fid' => $fid,
        'old_id' => $uri,
      ])
      ->execute();
  }
  
  /**
   * Check File is exist
   */
  public function checkFile($uri) {
    $query = $this->database->select('file_import');
    $query->fields('file_import', ['fid']);
    $query->condition('old_id', $uri);

    $fid = $query->execute()->fetchField();
    if($fid) {
      $file = $this->fileStorage->load($fid);
      return $file;
    }
    return NULL;
  }
  
  /**
   * Check Url
   */
  public function checkUrl($website) {
    if(substr($website, 0, 4) != 'http') {
      $website = "https://{$website}";
    }
    return $website;
  }
  
  /**
   * Retrieve media
   */
  public function getMedia($uri, $filename, $save_path, $type) {
    
    $media_field = NULL;
    $media = NULL;
    
    switch($type) {
      case 'image':
        $media_field = 'field_media_image';
        break;
    }
    
    $file = $this->getFile($uri, $filename, $save_path);

    if($file) {
      $checkMedia = $this->mediaStorage
        ->loadByProperties([
          'bundle' => $type,
          'field_old_id' => $uri,
        ]);
      
      if($checkMedia) {
        $media = reset($checkMedia);
      } else {
        $media = $this->mediaStorage
          ->create(['bundle' => $type]);
      }
      
      $media->field_old_id->setValue($uri);
      $media->get($media_field)->setValue($file->id());
      $media->save();
    }
    
    return $media;
  }
  
  /**
   * Retrieve file from HTTP REQUEST
   */
  public function getFile($uri, $filename, $save_path, $options = [], $external_path = 'edhec_prod') {
    $original_uri = $uri;
    
    $file = NULL;
    
    if(!empty($uri)) {
      $checkFile = $this->checkFile($uri);
      if($checkFile) {
        $file = $checkFile;
      } else {
        $uri = $this->getTarget($uri);
        $path = UrlHelper::encodePath($uri);
        
        if($external_path == 'edhec_prod') {
          $filepath = self::EDHEC_EDU_PUBLIC_PATH . $path;
        }
        if($external_path == 'entrepreneur') {
          $filepath = self::EDHEC_ENTREPENEUR_PUBLIC_PATH . $path;
        }
        
        $data = file_get_contents($filepath);
        $parse_url = parse_url($original_uri);
        
        if(isset($parse_url['query']) && strpos($parse_url['query'], 'ID_FICHIER') !== FALSE) {
          $id_query = explode('=', $parse_url['query']);
          $id_query = end($id_query);
          $filename = "com-univ-collaboratif.{$id_query}.pdf";
        }
        
        if($data) {
          $folder = "{$save_path}{$filename}";
          $file = $this->fileRepository->writeData($data, $folder, FileSystemInterface::EXISTS_RENAME);
          $this->addFileTrace($original_uri, $file->id());
        }
      }
    }
    
    return $file;
  }
  
  /**
   * Prepare Node
   */
  public function prepareNode($item, $node_type, $translation) {
    // Node original
    if(!$translation) {
      $checkNode = $this->checkNode($item, $node_type);
      if($checkNode) {
        $node = $checkNode;
      } else {
        $node = $this->nodeStorage
          ->create(['type' => $node_type]);
      }
    }
    
    // Node translation
    if($translation) {
      /*
        $node_es = $node->addTranslation('es');
        $node_es->title = 'Mi prueba!';
        $node_es->body->value = '<p>El cuerpo de mi nodo.</p>';
        $node_es->body->format = 'full_html';
        $node_es->save();
      */
      $checkNode = $this->getOriginalNode($item, $node_type);
      if($checkNode) {
        $original_node = $checkNode;
        if($original_node->hasTranslation($item->language)) {
          $node = $original_node->getTranslation($item->language);
        } else {
          $node = $original_node->addTranslation($item->language);
        }
      } else {
        d($item);
        d($item->tnid);
        d('issue');
        die;
      }
    }
    
    $node->title->setValue($item->title);
    $node->field_old_id->setValue($item->nid);
    $node->status->setValue($item->status);
    $node->setCreatedTime($item->created);
    $node->setChangedTime($item->changed);
    
    // Langcode
    if($item->language != 'und') {
      $node->langcode->setValue($item->language);
    }

    if($item->nid == '50545') {
      $node->langcode->setValue('en');
    }
    
    $alias = $this->getAlias($item);
    
    if($alias) {
      $node->path->setValue([
        'alias' => $alias,
        'pathauto' => PathautoState::SKIP,
      ]);
    } else {
      $node->path->setValue(NULL);
    }
    
    return $node;
  }
  
  public function getAlias($item) {
    // url_alias
    $connection = \Drupal\Core\Database\Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('url_alias', 'alias');
    $query->fields('alias', ['alias']);
    $query->condition('alias.language', $item->language);
    $query->condition('alias.source', "node/{$item->nid}");
    $query->orderBy('pid', 'DESC');
    $query->range(0, 1);
    $alias = $query->execute()->fetchField();
    
    if(!empty($alias)) {
      $alias = "/{$alias}";
    }
    
    return $alias;
  }
  
  /**
   * Retrieve Term
   */
  public function getTerm($items, $vocabulary) {
    $tids = [];
    if(!empty($items)) {
      $query = $this->termStorage
        ->getQuery()
        ->condition('vid', $vocabulary);
      
      if(is_array($items)) {
        // Array
        $query->condition('field_old_tid', $items, 'IN');
      } else {
        // Single Item
        $query->condition('field_old_tid', $items);
      }
      $tids = $query->execute();
    }
    return $tids;
  }
  
  /**
   * Retrieve Multiple Fields Term
   */
  public function getMultipleTerms($items, $vocabulary) {
    $tids = [];
    if(!empty($items)) {
      $query = $this->termStorage
        ->getQuery()
        ->condition('vid', $vocabulary);
      
      if(is_array($items)) {
        // Array
        $query->condition('field_old_tid_multiple', $items, 'IN');
      } else {
        // Single Item
        $query->condition('field_old_tid_multiple', $items);
      }
      $tids = $query->execute();
    }
    return $tids;
  }
  
  public function getOriginalNode($item, $node_type) {
    $tnid = $item->tnid;
    $checkNode = $this->nodeStorage
      ->loadByProperties([
        'type' => $node_type,
        'field_old_id' => $tnid,
      ]);
    
    if($checkNode) {
      return reset($checkNode);
    }
    return NULL;
  }
  
  public function checkNode($item, $node_type) {
    $nid = $item->nid;
    $node = $this->nodeStorage
      ->loadByProperties([
        'type' => $node_type,
        'field_old_id' => $nid,
      ]);
    if($node) {
      return reset($node);
    }
    return NULL;
  }
  
  public function getChapo($string) {
    $chapo = $this->cleanString($string);
    $chapo = Unicode::truncate($chapo, 200, TRUE, TRUE);
    return $chapo;
  }
  
  public function cleanString($str) {       
    $str = strip_tags($str);
    $str = html_entity_decode($str);
    $str = str_replace("\xC2\xA0", ' ', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    $str = trim($str);
    return $str;
  }
  
  // Cherche dans le text d'un champ RTE pour remplacer les balises Img et Lien par des Medias
  public function ckeditor($text) {
    $new_text = $this->ckeditorImages($text);
    return $new_text;
  }
  
  // Cherche dans le text d'un champ RTE pour remplacer les balises Img et Lien par des Fichiers
  public function ckeditorImages($text, $bundle) {
    
    $folder = 'public://ckeditor-inlines';
    $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    
    $path = drupal_get_path('module', 'custom_import') . '/src';
    include_once("{$path}/simple_html_dom.php");
    $html = str_get_html($text);
    
    // Si simple_dom_html a chargé correctement le html
    if($html) {
      foreach($html->find('img') as $element) {
        $src = $element->src;

        // Entrepreneur / Corpo
        $this->replaceFileCkeditor($element, 'img', $folder, $bundle);
      }
      
      foreach($html->find('a') as $element) {
        $src = $element->href;
        
        // Entrepreneur / Corpo
        $this->replaceFileCkeditor($element, 'a', $folder, $bundle);
      }
      
      return $html;
    }

    return $text;
  }
  
  public function replaceFileCkeditor($element, $type, $folder, $bundle) {
    $file = NULL;
    $external_uri = NULL;
    
    // Tag IMG
    if($type == 'img') {
      $src = $element->src;
      $title = $element->title;
      $alt = $element->alt;
    }
    
    // Tag A
    if($type == 'a') {
      $src = $element->href;
      $title = $element->title;
      $alt = $title;
    }
    
    if(strpos($src, '/sites/entrepreneurs/files/') !== FALSE
      || strpos($src, '/sites/www.edhec-portail.pprod.net/files/') !== FALSE) {
      $old_id = 'ckeditor:/' . $src;
      $checkFile = $this->checkFile($old_id);
      
      if($checkFile) {
        $file = $checkFile;
      } else {
        // Check if external Uri
        if(substr($src, 0, 4) != 'http') {
          if(strpos($src, '/sites/entrepreneurs/files/') !== FALSE) {
            $external_uri = "https://entrepreneurs.edhec.edu{$src}";
          }
          if(strpos($src, '/sites/www.edhec-portail.pprod.net/files/') !== FALSE) {
            $external_uri = "https://www.edhec.edu{$src}";
          }
        } else {
          $external_uri = $src;
        }
        
        // Save external Uri
        if($external_uri) {
          $data = file_get_contents($external_uri);
          if($data) {
            $filename = basename($external_uri);
            $filepath = "{$folder}/{$filename}";
            $file = $this->fileRepository->writeData($data, $filepath, FileSystemInterface::EXISTS_RENAME);
            $this->addFileTrace($old_id, $file->id());
          }
        }
      }

      if($file) {
        // Tag IMG
        if($type == 'img') {
          $element->src = $file->createFileUrl(TRUE);
        }
        
        // Tag A
        if($type == 'a') {
          $element->href = $file->createFileUrl(TRUE);
        }
      }
    }
    
    if($type == 'a' && !isset($file)) {
      if(strpos($src, '/node/') !== FALSE) {
        $explode_url = explode('/', $src);
        $nid = (int) end($explode_url);
        if($nid && is_integer($nid)) {
          $new_url = $this->getNewUrl($nid, $bundle);
          if($new_url) {
            $element->href = $new_url;
          }
        }
      }
    }
  }
  
  public function getNewUrl($nid, $bundle) {
    $node = $this->nodeStorage
      ->loadByProperties([
        'type' => $bundle,
        'field_old_id' => $nid,
      ]);
    if($node) {
      $node = reset($node);
      return $node->toUrl()->toString();
    }
    return FALSE;
  }
  
  public function replaceBr($text) {
    $text = strip_tags($text);
    return $text;
  }
  
}