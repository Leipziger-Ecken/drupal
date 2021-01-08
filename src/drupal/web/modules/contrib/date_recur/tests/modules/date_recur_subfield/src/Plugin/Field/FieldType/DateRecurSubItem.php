<?php

namespace Drupal\date_recur_subfield\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Extends Date Recur field.
 *
 * @FieldType(
 *   id = "date_recur_sub",
 *   label = @Translation("Date Recur Sub"),
 *   description = @Translation("Field subclassing date recur."),
 *   default_widget = "date_recur_basic_widget",
 *   default_formatter = "date_recur_basic_formatter",
 *   list_class = "\Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList"
 * )
 */
class DateRecurSubItem extends DateRecurItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['color'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Color'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['color'] = [
      'description' => 'Color',
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '16',
    ];

    return $schema;
  }

}
