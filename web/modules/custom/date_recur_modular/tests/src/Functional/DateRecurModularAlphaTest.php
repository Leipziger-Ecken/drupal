<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur_modular\Functional;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Alpha Widget.
 *
 * @group date_recur_modular_widget
 * @coversDefaultClass \Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularAlphaWidget
 */
class DateRecurModularAlphaTest extends WebDriverTestBase {

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
    $component['type'] = 'date_recur_modular_alpha';
    $component['settings'] = [];
    $display->setComponent('dr', $component);
    $display->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    $user->timezone = 'Asia/Singapore';
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Tests field widget input is converted to appropriate database values.
   *
   * @param array $values
   *   Array of form fields to submit.
   * @param array $expected
   *   Array of expected field normalized values.
   *
   * @dataProvider providerTestWidget
   */
  public function testWidget(array $values, array $expected): void {
    $entity = DrEntityTest::create();
    $entity->save();
    $this->drupalGet($entity->toUrl('edit-form'));

    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');

    $entity = DrEntityTest::load($entity->id());
    $this->assertEquals($expected, $entity->dr[0]->getValue());
  }

  /**
   * Data provider for testWidget()
   *
   * @return array
   *   Data for testing.
   */
  public function providerTestWidget(): array {
    $data = [];

    $data['once'] = [
      [
        'dr[0][mode]' => 'once',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => NULL,
        'infinite' => FALSE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    $data['multi'] = [
      [
        'dr[0][mode]' => 'multiday',
        'dr[0][daily_count]' => 3,
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '5:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=DAILY;INTERVAL=1;COUNT=3',
        'infinite' => FALSE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    $data['weekly'] = [
      [
        'dr[0][mode]' => 'weekly',
        'dr[0][weekdays][MO]' => TRUE,
        'dr[0][weekdays][WE]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,FR',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    $data['fortnightly'] = [
      [
        'dr[0][mode]' => 'fortnightly',
        'dr[0][weekdays][MO]' => TRUE,
        'dr[0][weekdays][WE]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First Friday of the month.
    $data['monthly 1 ordinal 1 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        // Set weekday first, ordinals will appear after it.
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=FR;BYSETPOS=1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First Thursday and Friday of the month.
    $data['monthly 1 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=1,2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First and second Friday of the month.
    $data['monthly 1,2 ordinal 1 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=FR;BYSETPOS=1,2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // First and second Thursday and Friday of the month.
    $data['monthly 1,2 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][1]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=1,2,3,4',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last Thursday of the month.
    $data['monthly -1 ordinal 1 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH;BYSETPOS=-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last Thursday and Friday of the month.
    $data['monthly -1 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-2,-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Second to last Thursday of the month.
    $data['monthly -2 ordinal 1 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH;BYSETPOS=-2',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Second to last Thursday and Friday of the month.
    $data['monthly -4,-3 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Last and Second to last Thursday and Friday of the month.
    $data['monthly -4,-3-2,-1 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][-1]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3,-2,-1',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    // Combination second and second to last Thursday and Friday of the month.
    $data['monthly -4,-3,3,4 ordinal 2 weekday'] = [
      [
        'dr[0][mode]' => 'monthly',
        'dr[0][start][date]' => '04/14/2015',
        'dr[0][start][time]' => '09:00:00am',
        'dr[0][end][date]' => '04/14/2015',
        'dr[0][end][time]' => '05:00:00pm',
        'dr[0][weekdays][TH]' => TRUE,
        'dr[0][weekdays][FR]' => TRUE,
        'dr[0][ordinals][2]' => TRUE,
        'dr[0][ordinals][-2]' => TRUE,
      ],
      [
        'value' => '2015-04-14T01:00:00',
        'end_value' => '2015-04-14T09:00:00',
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=TH,FR;BYSETPOS=-4,-3,3,4',
        'infinite' => TRUE,
        'timezone' => 'Asia/Singapore',
      ],
    ];

    return $data;
  }

  /**
   * Tests date/time/time zone is correct as loaded from storage.
   */
  public function testDefaultValuesFromStorage(): void {
    $entity = DrEntityTest::create();
    $entity->save();

    $entity->dr = [
      'value' => '2015-04-13T16:00:00',
      'end_value' => '2015-04-13T18:00:00',
      'rrule' => 'FREQ=WEEKLY;INTERVAL=1;COUNT=1',
      // This time zone should be different to currentuser.
      // Kigali: UTC+2 NO DST.
      'timezone' => 'Africa/Kigali',
    ];
    $entity->save();

    // Ensure all day is pre-checked if day is not full.
    $this->drupalGet($entity->toUrl('edit-form'));

    $this->assertSession()->fieldValueEquals('dr[0][start][date]', '2015-04-13');
    $this->assertSession()->fieldValueEquals('dr[0][start][time]', '18:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][end][date]', '2015-04-13');
    $this->assertSession()->fieldValueEquals('dr[0][end][time]', '20:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][time_zone]', 'Africa/Kigali');
  }

  /**
   * Tests dates adjusted to time zone from storage then re-save.
   *
   * Dates must save correctly when changing the time zone after loading the
   * form, then re-saving.
   */
  public function testTimeZoneChanged(): void {
    $entity = DrEntityTest::create();
    $entity->save();

    $entity->dr = [
      'value' => '2015-04-13T16:00:00',
      'end_value' => '2015-04-13T18:00:00',
      'rrule' => 'FREQ=WEEKLY;INTERVAL=1;UNTIL=20151011T140000Z',
      // UTC+2.
      'timezone' => 'Africa/Kigali',
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));

    $this->assertSession()->fieldValueEquals('dr[0][ends_mode]', 'date');
    $this->assertSession()->fieldValueEquals('dr[0][time_zone]', 'Africa/Kigali');
    $this->assertSession()->fieldValueEquals('dr[0][start][time]', '18:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][end][time]', '20:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][ends_date][date]', '2015-10-11');
    $this->assertSession()->fieldValueEquals('dr[0][ends_date][time]', '16:00:00');

    $values = [
      // Change to UTC+4.
      'dr[0][time_zone]' => 'Indian/Mauritius',
    ];
    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');

    // All values should be updated to account for different time zone, all
    // values above in field value assertions should be offset -4 hours.
    $entity = DrEntityTest::load($entity->id());
    $this->assertEquals('Indian/Mauritius', $entity->dr->timezone);
    $this->assertEquals('2015-04-13T14:00:00', $entity->dr->value);
    $this->assertEquals('2015-04-13T16:00:00', $entity->dr->end_value);
    $this->assertEquals('FREQ=WEEKLY;INTERVAL=1;UNTIL=20151011T120000Z', $entity->dr->rrule);
  }

}
