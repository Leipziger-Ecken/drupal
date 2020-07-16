<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur_modular\Functional;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Sierra Widget.
 *
 * @group date_recur_modular_widget
 * @coversDefaultClass \Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularSierraWidget
 */
class DateRecurModularSierraTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_modular',
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
  protected function setUp(): void {
    parent::setUp();

    $display = \Drupal::service('entity_display.repository')->getFormDisplay('dr_entity_test', 'dr_entity_test', 'default');
    $component = $display->getComponent('dr');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_modular_sierra';
    $component['settings'] = [];
    $display->setComponent('dr', $component);
    $display->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    $user->timezone = 'Asia/Singapore';
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Tests user without time zone.
   */
  public function testUserNoTimeZone(): void {
    // Turn of configurable time zone so \system_user_presave() doesn't
    // set a default timezone field value on save.
    \Drupal::configFactory()->getEditable('system.date')
      ->set('timezone.user.configurable', FALSE)
      ->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    // When this user is logged in it should get Sydney timezone.
    $user->timezone = '';
    $user->save();
    // Make sure timezone isn't changed elsewhere.
    $this->assertNull($user->timezone->value);
    $this->drupalLogin($user);

    // Test a date/time appears as all-day.
    $entity = DrEntityTest::create();
    $entity->dr = [
      // Time is 00:00am Sydney. (UTC+10)
      'value' => '2015-05-13T14:00:00',
      // Time is 11:59:59pm Sydney.
      'end_value' => '2015-05-14T13:59:59',
      'rrule' => 'FREQ=WEEKLY;INTERVAL=1;COUNT=1',
      // Doesnt matter what time zone storage is.
      'timezone' => 'Asia/Singapore',
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->checkboxChecked('dr[0][is_all_day]');

    // Test a date/time that does not appear as all-day.
    $entity = DrEntityTest::create();
    $entity->dr = [
      // Time is 00:00am Sydney. (UTC+10)
      'value' => '2015-05-13T14:00:00',
      // Time is 11:58:59pm Sydney.
      'end_value' => '2015-05-14T13:58:59',
      'rrule' => 'FREQ=WEEKLY;INTERVAL=1;COUNT=1',
      // Doesnt matter what time zone storage is.
      'timezone' => 'Asia/Singapore',
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->checkboxNotChecked('dr[0][is_all_day]');
  }

}
