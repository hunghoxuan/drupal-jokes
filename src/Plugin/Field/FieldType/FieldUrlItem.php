<?php

namespace Drupal\jokes_api\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'jokes_api' field type.
 *
 * @FieldType(
 *   id = "field_url1",
 *   label = @Translation("Embed Youtube video"),
 *   module = "jokes_api",
 *   description = @Translation("Output video from Youtube."),
 * )
 */
class FieldUrlItem extends FieldItemBase
{
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition)
  {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty()
  {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
  {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Youtube video URL'));

    return $properties;
  }
}
