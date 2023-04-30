<?php

namespace Drupal\jokes_api\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\jokes_api\Service\JokesApi;


/**
 * Provides a block
 *
 * @Block(
 *   id = "jokes_api_block",
 *   admin_label = @Translation("Jokes API block"),
 * )
 */
class JokesApiBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface
{
  /**
   * @var Service
   */
  private $service;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    JokesApi $service
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
    $page_size = $config[JokesApi::PARAM_PAGE_SIZE];
    $config[JokesApi::PARAM_API_URL] = $this->service->getApiUrl();

    // build an array of data to send to the JS file
    $jokes = $this->service->getJokes($page_size);
    $headers = [$this->t('Joke'), $this->t('Link')];
    $rows = [];
    foreach ($jokes as $joke) {
      $rows[] = [$joke['title'], Link::fromTextAndUrl($this->t('Open'), Url::fromUri($joke['url']))];
    }

    // make build array (output elements)
    $build = [];

    // show random joke by calling api url directly
    $build['random'] = [
      '#markup' => '<h2>Random Joke</h2><div id="jokes"></div>',
      '#attached' => [
        'library' => ['jokes_api/jokesapi'],
        'drupalSettings' => [
          'config' => $config,
        ],
      ],
      '#weight' => 1,
    ];

    $build['seperator1'] = [
      '#markup' => '<br/><h2>All Jokes</h2></b>',
      '#weight' => 2,
    ];

    // show existing nodes imported in db
    $build['rows'] = [
      '#type' => 'table',
      '#header' => $headers,
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
    $form[JokesApi::PARAM_PAGE_SIZE] = [
      '#type' => 'textfield',
      '#title' => t('Page Size'),
      '#default_value' => isset($config[JokesApi::PARAM_PAGE_SIZE]) ? $config[JokesApi::PARAM_PAGE_SIZE] : $this->service->getPageSize(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    // save custom settings when the form is submitted
    $this->setConfigurationValue(JokesApi::PARAM_PAGE_SIZE, $form_state->getValue(JokesApi::PARAM_PAGE_SIZE));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state)
  {
    $output_limit = $form_state->getValue(JokesApi::PARAM_PAGE_SIZE);

    // validate the input; needs a numeric value between 1 and 10
    if (!is_numeric($output_limit)) {
      $form_state->setErrorByName(JokesApi::PARAM_PAGE_SIZE, t('Needs to be an integer!'));
    }
    if ($output_limit < 1) {
      $form_state->setErrorByName(JokesApi::PARAM_PAGE_SIZE, t('Needs to be greater than zero!'));
    }
    if ($output_limit > 10) {
      $form_state->setErrorByName(JokesApi::PARAM_PAGE_SIZE, t('Max allowed is 10!'));
    }
  }
}
