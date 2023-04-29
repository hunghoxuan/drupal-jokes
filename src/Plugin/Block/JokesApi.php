<?php

namespace Drupal\jokes_api\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\jokes_api\Service\JokesApiService;


/**
 * Provides a block
 *
 * @Block(
 *   id = "jokes_api_block",
 *   admin_label = @Translation("Jokes API block"),
 * )
 */
class JokesApi extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface
{
  /**
   * @var Service
   */
  private $service;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    JokesApiService $service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jokes_api.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // get existing configuration for this block
    $config = $this->getConfiguration();
    $page_size = $config['page_size'];
    $config['api_url'] = $this->service->getApiUrl();

    // build an array of data to send to the JS file
    $rows = $this->service->getJokes($page_size);

    // make build array (output elements)
    $build = [];

    // show random joke by calling api url directly
    $build['random'] = [
      '#markup' => '<div id="jokes"></div>',
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

    // show existing nodes imported in db
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

    // show pager
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
      '#default_value' => isset($config['page_size']) ? $config['page_size'] : $this->service->getPageSize(),
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
