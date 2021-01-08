<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests token functionality provided by token.module.
 *
 * @group date_recur
 * @requires module token
 */
class DateRecurTokenTest extends KernelTestBase {

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
    'token',
    'text',
    'filter',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install core date formats.
    $this->installConfig(['system']);
  }

  /**
   * Tests tokens.
   */
  public function testTokens() {
    $user = User::create([
      'uid' => 2,
      // UTC+8.
      'timezone' => 'Asia/Singapore',
    ]);
    $this->container->get('current_user')->setAccount($user);

    $entity = DrEntityTest::create();
    $entity->dr = [
      'value' => '2014-06-15T23:00:00',
      'end_value' => '2014-06-16T07:00:00',
      'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
      'infinite' => '1',
      // UTC+7.
      'timezone' => 'Indian/Christmas',
    ];

    // Start date token.
    $replaced = \Drupal::token()->replace('[dr_entity_test:dr:start_date:long]', ['dr_entity_test' => $entity]);
    $this->assertEquals('Monday, June 16, 2014 - 07:00', $replaced);

    // End date token.
    $replaced = \Drupal::token()->replace('[dr_entity_test:dr:end_date:long]', ['dr_entity_test' => $entity]);
    $this->assertEquals('Monday, June 16, 2014 - 15:00', $replaced);
  }

}
