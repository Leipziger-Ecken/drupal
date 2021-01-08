<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests field validation failures as a result part grids.
 *
 * @group date_recur
 */
class DateRecurPartGridTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * A field config for testing.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $fieldConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    $this->fieldConfig = FieldConfig::create($field);
  }

  /**
   * Tests when nothing is allowed.
   */
  public function testAllowedAll() {
    $this->setPartSettings([
      'all' => TRUE,
      'frequencies' => [
        // Nothing is allowed here.
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(0, $violations->count());
  }

  /**
   * Tests when nothing is allowed.
   */
  public function testAllowedNothing() {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        // Nothing is allowed.
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = strip_tags((string) $violation->getMessage());
    $this->assertEquals('Weekly is not a permitted frequency.', $message);
  }

  /**
   * Tests when a frequency is allowed or disallowed.
   */
  public function testFrequency() {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => ['*'],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    // Try a disallowed frequency.
    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = strip_tags((string) $violation->getMessage());
    $this->assertEquals('Daily is not a permitted frequency.', $message);

    // Try an allowed frequency.
    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(0, $violations->count());
  }

  /**
   * Tests when some parts for a frequency is allowed.
   */
  public function testAllowedSomeParts() {
    $this->setPartSettings([
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => ['DTSTART', 'FREQ', 'COUNT', 'INTERVAL', 'WKST'],
      ],
    ]);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      // Include a disallowed part.
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(1, $violations->count());

    $violation = $violations->get(0);
    $message = strip_tags((string) $violation->getMessage());
    $this->assertEquals('By-day is not a permitted part.', $message);

    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      // Remove the disallowed BYDAY part.
      'rrule' => 'FREQ=WEEKLY;COUNT=3',
      'infinite' => '0',
      'timezone' => 'Australia/Sydney',
    ];

    /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $violations */
    $violations = $entity->foo->validate();
    $this->assertEquals(0, $violations->count());
  }

  /**
   * Sets parts settings then saves the field config.
   *
   * @param array $settings
   *   An array of parts settings.
   */
  protected function setPartSettings(array $settings) {
    $this->fieldConfig->setSetting('parts', $settings)->save();
  }

}
