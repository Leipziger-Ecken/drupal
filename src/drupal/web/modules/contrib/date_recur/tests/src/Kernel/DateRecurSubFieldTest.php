<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\DateRecurOccurrences;
use Drupal\date_recur_subfield\Plugin\Field\FieldType\DateRecurSubItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests fields subclassing date_recur.
 *
 * @group date_recur
 */
class DateRecurSubFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_subfield',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * Tests occurrence table is created for subclassed fields.
   */
  public function testOccurrenceTable() {
    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'abc',
      'type' => 'date_recur_sub',
      'settings' => [
        'datetime_type' => DateRecurSubItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $fieldConfig = FieldConfig::create([
      'field_name' => 'abc',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $fieldConfig->save();

    $entity = EntityTest::create();
    $entity->abc = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => 'Australia/Sydney',
    ];
    $entity->save();

    $tableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($fieldStorage);
    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    $this->assertTrue($actualExists);

    // Test deletion.
    $fieldStorage->delete();
    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    $this->assertFalse($actualExists);
  }

}
