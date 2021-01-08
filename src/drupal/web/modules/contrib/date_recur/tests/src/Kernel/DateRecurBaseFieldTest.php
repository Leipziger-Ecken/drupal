<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests base fields.
 *
 * @group date_recur
 */
class DateRecurBaseFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_entity_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
    $this->installEntitySchema('dr_entity_test_rev');
    $this->installEntitySchema('dr_entity_test_single');
    // Needed for uninstall tests.
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests date recur entity.
   */
  public function testDrEntityTest() {
    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];
    $entity->save();

    $tableName = 'date_recur__dr_entity_test__dr';
    $actualCount = $this->container->get('database')
      ->select($tableName)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(3, $actualCount);
  }

  /**
   * Tests occurrences table is dropped when date recur entity is uninstalled.
   *
   * @covers \Drupal\date_recur\DateRecurOccurrences::fieldStorageDelete
   */
  public function testOccurrenceTableDrop() {
    $this->container->get('module_installer')
      ->uninstall(['date_recur_entity_test']);

    $tableName = 'date_recur__dr_entity_test__dr';
    $actualExists = $this->container->get('database')
      ->schema()
      ->tableExists($tableName);
    $this->assertFalse($actualExists);
  }

}
