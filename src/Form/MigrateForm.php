<?php

namespace Drupal\jokes_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Promise\each_limit;


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
    $config = $this->config('jokes_api.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Migrate data from API to Drupal.'),
    ];

    $form['batch'] = [
      '#type' => 'select',
      '#title' => 'Choose batch',
      '#options' => [
        'batch_1' => $this->t('batch 1 - 1000 operations'),
        'batch_2' => $this->t('batch 2 - 20 operations.'),
      ],
    ];

    $form['rows_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of rows to migrate'),
      '#default_value' => 5,
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('api_url'),
    ];

    $form['node_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node Type'),
      '#default_value' => $config->get('node_type'),
    ];

    $form['default_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto publish imported nodes'),
      '#default_value' => $config->get('default_status'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Migrate',
    ];

    $form['submit2'] = [
      '#type' => 'delete_all',
      '#value' => 'Delete',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $url = $form_state->getValue('api_url');
    $default_status = $form_state->getValue('default_status');
    $node_type = $form_state->getValue('node_type');
    $rows_number = $form_state->getValue('rows_number');

    $config = $this->config('jokes_api.settings');
    $config->set('api_url', $url);
    $config->set('node_type', $node_type);
    $config->set('default_status', $default_status);

    $config->save();
    $i = 0;
    $client = new Client(['timeout' => 12]);
    $promises = [];

    $promises = function () use ($client, $url, $rows_number) {
      foreach (range(1, $rows_number) as $index) {
        echo "Starting $index query...\n";

        yield $client->getAsync($url)
          ->then(function ($response) use ($index) {
            echo "Request $index completed successfully.\n";

            return [
              'response' => $response->getBody(),
              'index'    => $index
            ];
          });
      }
    };

    $promise = each_limit(
      $promises(),
      $rows_number,
      function ($response, $index) use ($node_type, $default_status) {
        $data = json_decode(
          $response['response'],
          true
        );

        // processing response of the user
        $url = $data['url'];
        $content = $data['value'];
        $created = $data['created_at'];
        $id = $data['id'];

        // Create node object with attached file.
        $node = \Drupal\node\Entity\Node::create([
          'type'  => $node_type,
          'title' => $content,
          'field_content' => $content,
          'field_url' => $url,
          'field_id' => $id,
          'field_created' => $created,
          'status' => $default_status
        ]);
        $node->save();
        echo "Node $index $content saved.\n";
      },
      function ($reason, $index) use ($node_type, $default_status) {
        // do stuff
        echo "Request $index failed: $reason\n";
      }
    );

    $promise->wait();

    parent::submitForm($form, $form_state);
  }
}
