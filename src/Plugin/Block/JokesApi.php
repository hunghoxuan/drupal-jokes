<?php

namespace Drupal\jokes_api\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block
 *
 * @Block(
 *   id = "jokes_api_block",
 *   admin_label = @Translation("Jokes API block"),
 * )
 */
class JokesApi extends BlockBase implements BlockPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // get module settings
    $settings = \Drupal::config('jokes_api.settings');
    $api_url = $settings->get('api_url');
    $node_type = $settings->get('node_type');
    $showPublished = $settings->get('show_published');
    $page_size = $settings->get('page_size');

    // get existing configuration for this block
    $config = $this->getConfiguration();
    $page_size = $config['page_size'];
    $config['api_url'] = $api_url;

    // build an array of data to send to the JS file
    $rows = []; // rows data

    $query = \Drupal::entityQuery('node')
      ->accessCheck(false)
      ->condition('type', $node_type)
      ->sort('created', 'DESC')
      ->pager($page_size);

    if ($showPublished)
      $query->condition('status', 1);

    $nids = $query->execute();

    $output = '<div id="jokes"></div>';

    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $rows[] = [
        'nid' => $node->access('view') ? $node->id() : $this->t('Confidential'),
        'title' => $node->access('view') ?  $node->getTitle() : $this->t('Confidential'),
        # 'content' => $node->access('view') ? $node->get('field_content')->getString() : $this->t('Confidential'),
        # 'url' => $node->access('view') ? $node->get('field_url')->getString() : $this->t('Confidential'),
        'created' => $node->access('view') ? substr($node->get('field_created')->getString(), 0, 10) : $this->t('Confidential'),
      ];
    }

    // make build array (output elements)
    $build = [];
    $build['random'] = [
      '#markup' => $output,
      '#attached' => [
        'library' => ['jokes_api/jokesapi'],
        'drupalSettings' => [
          'config' => $config,
        ],
      ],
      '#weight' => 1,
    ];

    $build['seperator1'] = [
      '#markup' => '<br/>',
      '#weight' => 2,
    ];

    // add rows 
    $build['rows'] = [
      '#type' => 'table',
      '#header' => [$this->t('NID'), $this->t('Content'), $this->t('Created')],
      '#rows' => $rows,
      '#empty' => $this->t('No data found'),
      '#weight' => 20,
    ];

    $build['seperator2'] = [
      '#markup' => '<br/>',
      '#weight' => 21,
    ];

    // add pager
    $build['pager'] = [
      '#type' => 'pager',
      '#weight' => 30,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account, $return_as_object = FALSE)
  {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {

    $form = parent::blockForm($form, $form_state);

    // get existing configuration for this block
    $config = $this->getConfiguration();

    // Add a form field to the existing block config form
    $form['page_size'] = [
      '#type' => 'textfield',
      '#title' => t('Page Size'),
      '#default_value' => isset($config['page_size']) ? $config['page_size'] : \Drupal::config('jokes_api.settings')->get('page_size'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    // save custom settings when the form is submitted
    $this->setConfigurationValue('page_size', $form_state->getValue('page_size'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state)
  {
    $output_limit = $form_state->getValue('page_size');

    // validate the input; needs a numeric value between 1 and 10
    if (!is_numeric($output_limit)) {
      $form_state->setErrorByName('page_size', t('Needs to be an integer!'));
    }
    if ($output_limit < 1) {
      $form_state->setErrorByName('page_size', t('Needs to be greater than zero!'));
    }
    if ($output_limit > 10) {
      $form_state->setErrorByName('page_size', t('Max allowed is 10!'));
    }
  }
}
