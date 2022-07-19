<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteMediasForm extends FormBase {
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->nodeStorage = $instance->entityTypeManager->getStorage('node');
    $instance->mediaStorage = $instance->entityTypeManager->getStorage('media');
    $instance->fileStorage = $instance->entityTypeManager->getStorage('file');
    $instance->database = $container->get('database');
    return $instance;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return  'delete_medias_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    $form['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Files'),
    ];
    
    $form['delete_medias'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Medias'),
      '#submit' => ['::deleteMediasSubmit'],
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->database->select('file_import');
    $query->fields('file_import', ['fid']);
    $results = $query->execute();
    
    $fids = [];
    foreach($results as $result) {
      $fids[] = $result->fid;
    }
    
    if($fids) {
      $files = $this->fileStorage->loadMultiple($fids);
      $this->fileStorage->delete($files);
    }
    
    $this->database->truncate('file_import')->execute();
    
    $this->messenger()->addStatus($this->t('Files deleted'));
  }
  
  /**
   * {@inheritdoc}
   */
  public function deleteMediasSubmit(array &$form, FormStateInterface $form_state) {
    $mids = $this->mediaStorage
      ->getQuery()
      ->exists('field_old_id')
      ->execute();
    
    if($mids) {
      $medias = $this->mediaStorage
        ->loadMultiple($mids);
      $this->mediaStorage
        ->delete($medias);
    }
    
    $this->messenger()->addStatus($this->t('Medias deleted'));
  }
}