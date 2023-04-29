<?php

namespace Drupal\jokes_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use function GuzzleHttp\Promise\each_limit;
use Drupal\jokes_api\Service\JokesApiService;


/**
 * Class MigrateForm.
 *
 * @package Drupal\jokes_api\Form
 */
class MigrateForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jokes_api_migrate_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'jokes_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $service = JokesApiService::getInstance();

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Migrate data from API to Drupal.'),
    ];

    $form['rows_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of rows to migrate'),
      '#default_value' => 5,
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $service->getApiUrl(),
    ];

    $form['node_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $service->getNodeType(),
    ];

    $form['default_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto publish imported nodes'),
      '#default_value' => $service->getDefaultPublishStatus(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];

    $form['submit2'] = [
      '#type' => 'delete_all',
      '#value' => $this->t('Delete'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $service = JokesApiService::getInstance();

    $url = $form_state->getValue('api_url');
    $default_status = $form_state->getValue('default_status');
    $rows_number = $form_state->getValue('rows_number');

    // $promise->wait();
    $importedData = $service->getImportedJokes($url, $rows_number);

    foreach ($importedData as $data) {
      $url = $data['url'];
      $content = $data['value'];
      $created = $data['created_at'];
      $id = $data['id'];

      // Create node object with attached file.
      $service->saveJoke($content, $url, $id, $created, $default_status);
    }
    parent::submitForm($form, $form_state);
  }
}
