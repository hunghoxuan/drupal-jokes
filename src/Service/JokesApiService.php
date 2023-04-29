<?php

namespace Drupal\jokes_api\Service;

use GuzzleHttp\Client;
use function GuzzleHttp\Promise\each_limit;

/**
 * @ingroup jokes_api
 */
class JokesApiService
{
  const MODULE_NAME = 'jokes_api';

  public function getModuleId()
  {
    return self::MODULE_NAME;
  }

  #region "Singleton"
  static ?JokesApiService $instance = null;
  public static function getInstance()
  {
    if (self::$instance == null)
      self::$instance = new JokesApiService();

    return self::$instance;
  }
  #endregion

  #region "Install / Uninstall functions"
  public function install()
  {
    // Set default values for config which require dynamic values.
    \Drupal::configFactory()->getEditable($this->getModuleSettingsId())
      ->set('api_url', 'https://api.chucknorris.io/jokes/random')
      ->set('node_type', 'jokes')
      ->set('page_size', 5)
      ->set('default_status', false)
      ->set('show_published', true)
      ->save();
  }

  public function uninstall()
  {
    if ($this->entityTypeHasField('node', 'field_created')) { // avoid entityTypeManager->loadByProperties crashing
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
  public function createJokesBlock()
  {
    $customblock = \Drupal::service('plugin.manager.block')->createInstance('jokes_api_block');
    if (isset($customblock) && !empty($customblock)) {
      $customblock->setConfigurationValue('page_size', $this->getPageSize());
      $customblock->setConfigurationValue('show_published', $this->getShowPublished());
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
        'nid' => $node->access('view') ? $node->id() : $this->t('Confidential'),
        'title' => $node->access('view') ?  $node->getTitle() : $this->t('Confidential'),
        'created' => $node->access('view') ? substr($node->get('field_created')->getString(), 0, 10) : $this->t('Confidential'),
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
    return self::getSettings()->get('api_url');
  }

  public function getPageSize()
  {
    return self::getSettings()->get('page_size');
  }

  public function getDefaultPublishStatus()
  {
    return self::getSettings()->get('default_status');
  }

  public function getNodeType()
  {
    return self::getSettings()->get('node_type');
  }

  public function getShowPublished()
  {
    return self::getSettings()->get('show_published');
  }
  #endregion
}
