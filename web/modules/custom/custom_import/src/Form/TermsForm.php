<?php

namespace Drupal\custom_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TermsForm extends FormBase {
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The taxonomy term storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->termStorage = $instance->entityTypeManager->getStorage('taxonomy_term');
    return $instance;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'terms_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    
    // $form['import_localisation'] = [
      // '#type' => 'submit',
      // '#value' => $this->t('Localisation - Campus'),
      // '#submit' => ['::localisationSubmit'],
    // ];
    
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
  public function localisationSubmit(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection('default', 'edhec_prod');
    
    $query = $connection->select('taxonomy_term_data', 'term');
    $query->condition('term.vid', 13);
    $query->leftJoin('field_data_field_id_ws', 'field_id_ws', "field_id_ws.entity_id = term.tid AND field_id_ws.bundle = 'localisation' AND field_id_ws.entity_type = 'taxonomy_term'");
    $query->fields('term', ['tid', 'name']);
    $query->fields('field_id_ws', ['field_id_ws_value']);
    
    $results = $query->execute();
    
    $list = [];
    
    foreach($results as $result) {
      $checkTerm = $this->checkLocalisation($result->tid);
      if(!$checkTerm) {
        $term = $this->termStorage
          ->create(['vid' => 'campus']);
      } else {
        $term = $checkTerm;
      }
      $term->setName($result->name);
      $term->field_old_tid->setValue($result->tid);
      
      if(!empty($result->field_id_ws_value)) {
        $term->field_ws_camp_tid->setValue($result->field_id_ws_value);
      }
      $term->save();
    }
    
    $this->messenger()->addStatus($this->t('Terminé'));
  }
  
  public function checkLocalisation($old_tid) {
    $terms = $this->termStorage
      ->loadByProperties([
        'vid' => 'campus',
        'field_old_tid' => $old_tid,
      ]);

    if($terms) {
      return reset($terms);
    }
    return NULL;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
  
}