<?php

namespace Drupal\top30\Form;

use Drupal\Core\Form\FormBase;
use Drupal\custom\Classes\Custom;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Top30Form.
 */
class Top30Form extends FormBase {

  protected $number = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'top30_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    global $base_url;

    $header = array(
      $this->t('TOP30'),
      $this->t('Total')
    );

    $db = \Drupal::database();
    $query = $db->select('top30_tokens', 'ts');
    $query->fields('ts', array('token', 'date_debut', 'date_fin'));
    $query->orderBy('id', 'DESC');
    $result = $query->execute();

    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
    ->orderByHeader($header);

    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit(2);

    $rows = array();
    foreach($result as $row) {
      $rows[$row->token] = array(
        new FormattableMarkup('<a href=":link">@name</a>', [':link' => $base_url . '/admin/top30/edit/' . $row->token, '@name' => Custom::fixDateTM($row->date_debut, "%d %B %y").' '. $this->t('au') .' '.Custom::fixDateTM($row->date_fin, "%d %B %y")]),
        0
      );
    }

    $form['action_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Action : '),
      '#options' => [
        'export' => $this->t('Export as Excel'),
        'delete' => utf8_encode($this->t('Supprimer les TOP30 sélectionnés'))
      ],
    ];

    $form['table'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Users'),
      '#header' => $header,
      '#options' => $rows,
      '#empty' => utf8_encode(t('Aucun TOP30 trouvé!')),
    ];

    // Finally add the pager.
    $form['pager'] = array(
      '#type' => 'pager'
    );

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#description' => $this->t('Submit, #type = submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    die(var_dump($form_state->getValues()['table']));
    foreach ($form_state->getValues() as $key => $value) {
      var_dump($value);
      //drupal_set_message($key . ': ' . $value);
    }
    die();

  }

}
