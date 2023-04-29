<?php

namespace Drupal\jokes_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Session\AccountInterface;

/**
 * @ingroup jokes_api
 */
class JokesAPIController extends ControllerBase
{

  /**
   * Constructs a new BlockController instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user)
  {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName()
  {
    return 'jokes_api';
  }

  /**
   * Generate a render array with our templated content.
   *
   * @return array
   *   A render array.
   */
  public function list()
  {
    $block_content = 'Jokes API Demo';

    $customblock = \Drupal::service('plugin.manager.block')->createInstance('jokes_api_block');
    if (isset($customblock) && !empty($customblock)) {
      $settings = \Drupal::config('jokes_api.settings');

      $customblock->setConfigurationValue('page_size', $settings->get('page_size'));
      $customblock->setConfigurationValue('show_published', $settings->get('show_published'));

      $block_content = $customblock->build();
      return $block_content;
    }

    $build = [
      'main' => [
        '#theme' => 'jokes_api_list',
        '#attributes' => [
          'jokes_api' => $block_content,
        ],
      ],
    ];
    return $build;
  }
}
