<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the results of 'date_recur_date' field plugin.
 *
 * Tests field plugin for start and end date columns on field and occurrence
 * tables.
 *
 * @coversDefaultClass \Drupal\date_recur\Plugin\views\field\DateRecurDate
 *
 * @group date_recur
 */
class DateRecurViewsFieldAttachedTest extends DateRecurViewsFieldTest {

  /**
   * {@inheritdoc}
   */
  public static $testViews = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp(FALSE);

    // This is the name of the attached field.
    $this->fieldName = 'dr_attached';

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'dr_entity_test',
      'field_name' => $this->fieldName,
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $field = [
      'field_name' => $this->fieldName,
      'entity_type' => 'dr_entity_test',
      'bundle' => 'dr_entity_test',
    ];
    FieldConfig::create($field)->save();

    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    \Drupal::service('entity_type.manager')->clearCachedDefinitions();
  }

}
