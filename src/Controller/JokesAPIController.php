<?php

namespace Drupal\jokes_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jokes_api\Service\JokesApiService;

/**
 * @ingroup jokes_api
 */
class JokesAPIController extends ControllerBase
{
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Service for Jokes API.
   *
   * @var JokesApiService
   */
  protected $service;

  /**
   * Constructs a new Controller instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param JokesApiService $service
   */
  public function __construct(AccountInterface $current_user, JokesApiService $service)
  {
    $this->currentUser = $current_user;
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('current_user'),
      $container->get('jokes_api.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName()
  {
    return JokesApiService::MODULE_NAME;
  }

  /**
   * Auto add Jokes block to the page.
   *
   * @return array
   *   A render array for list function.
   */
  public function list()
  {
    $block_content = 'Jokes API Demo';

    $customblock = $this->service->createJokesBlock();
    if (isset($customblock) && !empty($customblock)) {
      return $customblock->build();
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
