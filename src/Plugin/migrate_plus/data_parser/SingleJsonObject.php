<?php

namespace Drupal\jokes_api\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "single_json_object",
 *   title = @Translation("Single JSON object as root")
 * )
 */
class SingleJsonObject extends Json
{

  protected function has_string_keys(array $array)
  {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
  }

  protected function getSourceData($url): array
  {
    $source_data = parent::getSourceData($url);

    if ($this->has_string_keys($source_data)) {
      $sequential_array = [];
      $sequential_array[] = $source_data;
      $source_data = $sequential_array;
    }

    var_dump($source_data);
    return $source_data;
  }
}
