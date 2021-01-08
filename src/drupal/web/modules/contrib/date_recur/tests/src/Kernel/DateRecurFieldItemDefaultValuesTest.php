<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests date_recur field with default values configured at the instance level.
 *
 * Default values need to evaluate FALSE per DateRecurItem::isEmpty
 * otherwise the values will be cleared out before display.
 *
 * Testing time zones:
 *  - Default date time zone:  Oslo (UTC+2)
 *  - Default time zone: Christmas (UTC+7).
 *  - Current user time zone: Singapore (UTC+8)
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem
 */
class DateRecurFieldItemDefaultValuesTest extends KernelTestBase {

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
   * An unsaved base field override entity for 'dr' field.
   *
   * @var \Drupal\Core\Field\Entity\BaseFieldOverride
   */
  protected $baseFieldOverride;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('dr_entity_test');
    $this->installConfig(['system']);

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $baseFields = $entityFieldManager->getBaseFieldDefinitions('dr_entity_test');
    $this->baseFieldOverride = BaseFieldOverride::createFromBaseFieldDefinition($baseFields['dr'], 'dr_entity_test');

    $user = User::create([
      'uid' => 2,
      'timezone' => 'Asia/Singapore',
    ]);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Tests default behaviour.
   */
  public function testDefaults() {
    $this->baseFieldOverride->setDefaultValue([
      [
        'default_date_type' => 'relative',
        'default_date' => '12th April 2013 3pm',
        'default_end_date_type' => 'relative',
        'default_end_date' => '12th April 2013 4pm',
        'default_date_time_zone' => 'Europe/Oslo',
        'default_time_zone' => 'Indian/Christmas',
        'default_rrule' => 'FREQ=DAILY;COUNT=100',
      ],
    ]);
    $this->baseFieldOverride->save();

    $entity = DrEntityTest::create();
    $this->assertEquals('2013-04-12T13:00:00', $entity->dr->value);
    $this->assertEquals('2013-04-12T14:00:00', $entity->dr->end_value);
    $this->assertEquals('Indian/Christmas', $entity->dr->timezone);
    $this->assertEquals('FREQ=DAILY;COUNT=100', $entity->dr->rrule);

    $entity->save();
    // Value is kept after save.
    $this->assertEquals(1, $entity->dr->count());
  }

  /**
   * Tests time zone from current user.
   */
  public function testDefaultCurrentUser() {
    $this->baseFieldOverride->setDefaultValue([
      [
        'default_date_type' => 'relative',
        'default_date' => '12th April 2013 3pm',
        'default_end_date_type' => 'relative',
        'default_end_date' => '12th April 2013 4pm',
        'default_date_time_zone' => 'Europe/Oslo',
        'default_time_zone' => '',
      ],
    ]);
    $this->baseFieldOverride->save();

    $entity = DrEntityTest::create();
    $this->assertEquals('2013-04-12T13:00:00', $entity->dr->value);
    $this->assertEquals('2013-04-12T14:00:00', $entity->dr->end_value);
    $this->assertEquals('Asia/Singapore', $entity->dr->timezone);
  }

}
