<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests date_recur fields.
 *
 * @group date_recur
 */
class DateRecurFieldTest extends KernelTestBase {

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
    FieldConfig::create($field)->save();
  }

  /**
   * Tests storage timezone is returned.
   *
   * Not the timezone used for current request, or default to UTC per storage.
   */
  public function testOccurrencesTimezone() {
    // Set the timezone to something different than UTC or storage.
    date_default_timezone_set('Pacific/Wake');

    $tzChristmas = new \DateTimeZone('Indian/Christmas');
    $entity = EntityTest::create();
    $entity->foo = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      'timezone' => $tzChristmas->getName(),
    ];

    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */
    $item = $entity->get('foo')[0];
    $occurrences = $item->getHelper()
      ->getOccurrences(NULL, NULL, 1);

    // Christmas island is UTC+7, so start time will be 6am.
    $assertDateStart = new \DateTime('6am 2014-06-16', $tzChristmas);
    $assertDateEnd = new \DateTime('2pm 2014-06-16', $tzChristmas);

    $this->assertTrue($assertDateStart == $occurrences[0]->getStart());
    $this->assertEquals($tzChristmas->getName(), $occurrences[0]->getStart()->getTimezone()->getName());
    $this->assertTrue($assertDateEnd == $occurrences[0]->getEnd());
    $this->assertEquals($tzChristmas->getName(), $occurrences[0]->getEnd()->getTimezone()->getName());
  }

}
