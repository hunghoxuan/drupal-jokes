<?php

namespace Drupal\jokes_api\Service;

use GuzzleHttp\Client;
use function GuzzleHttp\Promise\each_limit;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * @ingroup jokes_api
 */
class JokesApi
{
  const MODULE_NAME = 'jokes_api';
  const API_URL = 'https://api.chucknorris.io/jokes/random';

  const NODE_TYPE = 'jokes';
  const FIELD_CREATED = 'field_created';
  const FIELD_ID = 'field_id';
  const FIELD_URL = 'field_url';
  const FIELD_CONTENT = 'field_content';

  const PARAM_API_URL = 'api_url';
  const PARAM_NODE_TYPE = 'node_type';
  const PARAM_PAGE_SIZE = 'page_size';
  const PARAM_DEFAULT_STATUS = 'default_status';
  const PARAM_SHOW_PUBLISHED = 'show_published';

  public function getModuleId()
  {
    return self::MODULE_NAME;
  }

  #region "Singleton"
  static ?JokesApi $instance = null;
  public static function getInstance()
  {
    if (self::$instance == null)
      self::$instance = new JokesApi();

    return self::$instance;
  }
  #endregion

  #region "Install / Uninstall functions"
  public function install()
  {
    // Set default values for config which require dynamic values.
    \Drupal::configFactory()->getEditable($this->getModuleSettingsId())
      ->set(JokesApi::PARAM_API_URL, JokesApi::API_URL)
      ->set(JokesApi::PARAM_NODE_TYPE, JokesApi::NODE_TYPE)
      ->set(JokesApi::PARAM_PAGE_SIZE, 5)
      ->set(JokesApi::PARAM_DEFAULT_STATUS, false)
      ->set(JokesApi::PARAM_SHOW_PUBLISHED, true)
      ->save();
  }

  public function uninstall()
  {
    if ($this->entityTypeHasField('node', JokesApi::FIELD_CREATED)) { // avoid entityTypeManager->loadByProperties crashing
      $nids = \Drupal::entityQuery("node")
        ->condition("type", $this->getNodeType())
        ->accessCheck(false)
        ->execute();

      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");

      if (!empty($nids)) {
        foreach (array_chunk($nids, 50) as $chunk) {
          $nodes = $storage_handler->loadMultiple($chunk);
          $storage_handler->delete($nodes);
        }
      };
    }
  }

  public function clearCache()
  {
    // Clear the render cache. but seems not working well.
    \Drupal::service('page_cache_kill_switch')->trigger();
    \Drupal::service('cache.render')->invalidateAll();
    \Drupal::service('twig')->invalidate();
  }

  /**
   * Check if an entity type has a field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   Returns a TRUE if the entity type has the field.
   */
  function entityTypeHasField($entity_type = 'node', $field_name)
  {
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);

    foreach ($bundles as $bundle => $label) {
      $all_bundle_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
      if (isset($all_bundle_fields[$field_name])) {
        return TRUE;
      }
    }

    return FALSE;
  }
  #endregion

  #region UI functions
  /**
   * Get a list of toolbar links for testing toolbar routes.
   */
  public function getToolbarMenus()
  {
    return [
      'List' => [JokesApi::MODULE_NAME . '.list'],
      'Migrate' => [JokesApi::MODULE_NAME . '.migrate'],
      'Settings' => [JokesApi::MODULE_NAME . '.settings'],
      'Manage Data' => ['system.admin_content', ['type' => JokesApi::NODE_TYPE]],
      'Logs' => ['dblog.overview', ['type[]' => JokesAPi::MODULE_NAME]],
      'Modules' => ['system.modules_list', ['type' => JokesApi::NODE_TYPE], 'edit-modules-ffw']
    ];
  }

  public function getToolbarLinks()
  {
    $menus = $this->getToolbarMenus();
    foreach ($menus as $module => $route) {
      $links[$module] = [
        'title' => Html::escape($module),
        'url' => count($route) == 1 ? Url::fromRoute($route[0]) : Url::fromRoute($route[0], $route[1]),
        'attributes' => [
          'class' => [Html::getClass($module)],
          'title' => Html::escape($module),
        ],
        'fragment' => count($route) == 3 ? $route[2] : null,
      ];
    }
  }

  public function createJokesBlock()
  {
    $customblock = \Drupal::service('plugin.manager.block')->createInstance('jokes_api_block');
    if (isset($customblock) && !empty($customblock)) {
      $customblock->setConfigurationValue(JokesApi::PARAM_PAGE_SIZE, $this->getPageSize());
      $customblock->setConfigurationValue(JokesApi::PARAM_SHOW_PUBLISHED, $this->getShowPublished());
    }
    return $customblock;
  }

  public function createJokesBlockBuild()
  {
    $customblock = $this->createJokesBlock();
    if ($customblock)
      return $customblock->build();
    return [];
  }
  #endregion

  #region "Logs"
  public function logInfo($message)
  {
    \Drupal::logger(self::MODULE_NAME)->notice($message);
  }

  public function logError($message)
  {
    \Drupal::logger(self::MODULE_NAME)->error($message);
  }
  #endregion

  #region "Import functions"
  public function getImportedJokes($url, $rows_number)
  {
    $client = new Client(['timeout' => 12]);
    $promises = [];
    $data = [];

    $promises = function () use ($client, $url, $rows_number) {
      foreach (range(1, $rows_number) as $index) {

        // if ($index == 3 || $index == 5) // simulate error
        //   $url = str_replace('random', 'random1', $url);

        yield $client->getAsync($url)
          ->then(function ($response) use ($index) {
            return $response->getBody();
          });
      }
    };

    $promise = each_limit(
      $promises(),
      $rows_number,
      // success callback
      function ($response) use (&$data) {
        $data[] = json_decode(
          $response,
          true
        );
      },
      // error callback
      function ($exception, $index) {
        $this->logError("Import #$index failed: " . $exception->getMessage());
      }
    );

    $promise->wait();
    return $data;
  }
  #endregion

  #region "Data functions"
  // save Joke
  public function saveJoke($content, $url, $id, $created, $default_status)
  {
    $node = \Drupal\node\Entity\Node::create([
      'type'  => $this->getNodeType(),
      'title' => $content,
      'field_content' => $content,
      'field_url' => $url,
      'field_id' => $id,
      'field_created' => $created,
      'status' => $default_status
    ]);
    $node->save();
  }

  // get Jokes data for the block
  public function getJokes($page_size)
  {
    $rows = []; // rows data

    $query = \Drupal::entityQuery('node')
      ->accessCheck(false)
      ->condition('type', $this->getNodeType())
      ->sort('created', 'DESC')
      ->pager($page_size);

    if ($this->getShowPublished())
      $query->condition('status', 1);

    $nids = $query->execute();
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $rows[] = [
        'title' => $node->getTitle(),
        'url' => $node->get('field_url')->getString(),
        'id' => $node->get('field_id')->getString(),
        'content' => $node->get('field_content')->getString(),
        'created' => substr($node->get('field_created')->getString(), 0, 10),
      ];
    }
    return $rows;
  }

  #endregion

  #region "Settings"
  public function getModuleServiceId()
  {
    return self::MODULE_NAME . '.service';
  }

  public function getModuleSettingsId()
  {
    return self::MODULE_NAME . '.settings';
  }

  public function getSettings()
  {
    return \Drupal::config($this->getModuleSettingsId());
  }

  public function getSettingsForEdit()
  {
    return \Drupal::configFactory()->getEditable($this->getModuleSettingsId());
  }

  public function getApiUrl()
  {
    return self::getSettings()->get(JokesApi::PARAM_API_URL);
  }

  public function getPageSize()
  {
    return self::getSettings()->get(JokesApi::PARAM_PAGE_SIZE);
  }

  public function getDefaultPublishStatus()
  {
    return self::getSettings()->get(JokesApi::PARAM_DEFAULT_STATUS);
  }

  public function getNodeType()
  {
    return self::getSettings()->get(JokesApi::PARAM_NODE_TYPE);
  }

  public function getShowPublished()
  {
    return self::getSettings()->get(JokesApi::PARAM_SHOW_PUBLISHED);
  }
  #endregion
}
