<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\DateRecurOccurrences;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests occurrence tables schema.
 *
 * @group date_recur
 */
class DateRecurOccurrenceTableSchemaTest extends KernelTestBase {

  /**
   * Name of field for testing.
   *
   * @var string
   */
  protected $fieldName;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // This is the name of the base field.
    $this->fieldName = 'dr';
  }

  /**
   * Tests occurrence table schema for non revisionable entities.
   */
  public function testNonRevisionableOccurrenceTableSchema() {
    $testEntityType = 'dr_entity_test';
    $this->installEntitySchema($testEntityType);

    // Check again this entity type is not revisionable.
    $definition = \Drupal::entityTypeManager()->getDefinition($testEntityType);
    $this->assertFalse($definition->isRevisionable());

    $definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($testEntityType);
    $tableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($definitions[$this->fieldName]);

    $schema = \Drupal::database()->schema();
    $this->assertFalse($schema->fieldExists($tableName, 'revision_id'));
  }

  /**
   * Tests occurrence table schema for revisionable entities.
   */
  public function testRevisionableOccurrenceTableSchema() {
    $testEntityType = 'dr_entity_test_rev';
    $this->installEntitySchema($testEntityType);

    // Check again this entity type is not revisionable.
    $definition = \Drupal::entityTypeManager()->getDefinition($testEntityType);
    $this->assertTrue($definition->isRevisionable());

    $definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($testEntityType);
    $tableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($definitions[$this->fieldName]);

    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->fieldExists($tableName, 'revision_id'));
  }

}
