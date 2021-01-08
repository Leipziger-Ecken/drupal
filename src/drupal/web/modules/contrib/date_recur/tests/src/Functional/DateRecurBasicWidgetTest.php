<?php

namespace Drupal\Tests\date_recur\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests date recur basic widget.
 *
 * For some reason there are problems (as of Oct 2018) with filling date and
 * time fields with WebDriver. Using BTB in the mean time.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\Plugin\Field\FieldWidget\DateRecurBasicWidget
 */
class DateRecurBasicWidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_basic_widget_test',
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

    $display = entity_get_form_display('dr_entity_test', 'dr_entity_test', 'default');
    $component = $display->getComponent('dr');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('dr', $component);
    $display->save();

    $user = $this->drupalCreateUser(['administer entity_test content']);
    $user->timezone = 'Asia/Singapore';
    $user->save();
    $this->drupalLogin($user);
  }

  /**
   * Test value from DB displays correctly.
   */
  public function testEditForm() {
    $entity = DrEntityTest::create();
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    $timeZone = 'Indian/Christmas';
    $entity->dr = [
      [
        // 10am-4pm weekdaily.
        'value' => '2008-06-15T22:00:00',
        'end_value' => '2008-06-17T06:00:00',
        'rrule' => $rrule,
        // UTC+7.
        'timezone' => $timeZone,
      ],
    ];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('dr[0][value][date]', '2008-06-16');
    $this->assertSession()->fieldValueEquals('dr[0][value][time]', '05:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][date]', '2008-06-17');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][time]', '13:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][timezone]', $timeZone);
    $this->assertSession()->fieldValueEquals('dr[0][rrule]', $rrule);
  }

  /**
   * Tests submitted values make it into database for new entities.
   */
  public function testSavedFormNew() {
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    // UTC-5.
    $timeZone = 'America/Bogota';
    $edit = [
      'dr[0][value][date]' => '2008-06-17',
      // This is the time in Bogota.
      'dr[0][value][time]' => '03:00:01',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:04',
      'dr[0][timezone]' => $timeZone,
      'dr[0][rrule]' => $rrule,
    ];

    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalGet($url);
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('has been created.');

    $entity = $this->getLastSavedDrEntityTest();
    $expected = [
      'value' => '2008-06-17T08:00:01',
      'end_value' => '2008-06-17T17:00:04',
      'rrule' => $rrule,
      'timezone' => $timeZone,
      'infinite' => TRUE,
    ];
    $this->assertEquals($expected, $entity->dr[0]->toArray());
  }

  /**
   * Tests submitted values make it into database for pre-existing entities.
   */
  public function testSavedFormEdit() {
    $entity = DrEntityTest::create();
    $rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    $timeZone = 'America/Bogota';
    $value = [
      'value' => '2008-06-17T08:00:01',
      'end_value' => '2008-06-17T17:00:04',
      'rrule' => $rrule,
      'timezone' => $timeZone,
      'infinite' => TRUE,
    ];
    $entity->dr = [$value];
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));
    // Submit the values as is.
    $this->drupalPostForm(NULL, NULL, 'Save');
    $this->assertSession()->pageTextContains('has been updated.');

    // Reload the entity from storage.
    $entity = $this->getLastSavedDrEntityTest();
    $this->assertEquals($value, $entity->dr[0]->toArray());
  }

  /**
   * Tests inherited validation.
   *
   * Tests validation that comes automatically from date range. Specifically,
   * assert end date comes on or after start date.
   */
  public function testInheritedValidation() {
    $edit = [
      'dr[0][value][date]' => '2008-06-17',
      'dr[0][value][time]' => '03:00:00',
      'dr[0][end_value][date]' => '2008-06-15',
      'dr[0][end_value][time]' => '03:00:00',
      'dr[0][timezone]' => 'America/Chicago',
      'dr[0][rrule]' => 'FREQ=DAILY',
    ];
    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalPostForm($url, $edit, 'Save');
    $this->assertSession()->pageTextContains('end date cannot be before the start date');
  }

  /**
   * Tests start date must be set if end date is set.
   */
  public function testStartDateSetIfEndPosted() {
    $edit = [
      'dr[0][value][date]' => '',
      'dr[0][value][time]' => '',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:04',
      'dr[0][timezone]' => 'America/Chicago',
      'dr[0][rrule]' => 'FREQ=DAILY',
    ];
    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalPostForm($url, $edit, 'Save');
    $this->assertSession()->pageTextContains('Start date must be set if end date is set.');
  }

  /**
   * Tests default values appear in widget.
   */
  public function testDefaultValues() {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $baseFields = $entityFieldManager->getBaseFieldDefinitions('dr_entity_test');
    $baseFieldOverride = BaseFieldOverride::createFromBaseFieldDefinition($baseFields['dr'], 'dr_entity_test');
    // Default values need to evaluate FALSE per DateRecurItem::isEmpty
    // otherwise the values will be cleared out before display.
    $baseFieldOverride->setDefaultValue([
      [
        'default_date_type' => 'relative',
        'default_date' => '12th April 2013 3pm',
        'default_end_date_type' => 'relative',
        'default_end_date' => '12th April 2013 4pm',
        'default_date_time_zone' => 'Europe/Oslo',
        'default_time_zone' => 'Indian/Christmas',
        'default_rrule' => 'FREQ=WEEKLY;COUNT=995',
      ],
    ]);
    $baseFieldOverride->save();

    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalGet($url);
    // 3pm/4pm Oslo (UTC+2) -> 8pm/9pm Christmas (UTC+7).
    $this->assertSession()->fieldValueEquals('dr[0][value][date]', '2013-04-12');
    $this->assertSession()->fieldValueEquals('dr[0][value][time]', '20:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][date]', '2013-04-12');
    $this->assertSession()->fieldValueEquals('dr[0][end_value][time]', '21:00:00');
    $this->assertSession()->fieldValueEquals('dr[0][timezone]', 'Indian/Christmas');
    $this->assertSession()->fieldValueEquals('dr[0][rrule]', 'FREQ=WEEKLY;COUNT=995');
  }

  /**
   * Tests invalid rule.
   */
  public function testInvalidRule() {
    $edit = [
      'dr[0][value][date]' => '2008-06-17',
      'dr[0][value][time]' => '12:00:00',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:00',
      'dr[0][timezone]' => 'America/Chicago',
      'dr[0][rrule]' => $this->randomMachineName(),
    ];
    $url = Url::fromRoute('entity.dr_entity_test.add_form');
    $this->drupalPostForm($url, $edit, 'Save');
    $this->assertSession()->pageTextContains('Repeat rule is formatted incorrectly.');
  }

  /**
   * Tests if field is set to required, only start date is required.
   *
   * End date must never be required, value is copied over from start date.
   */
  public function testRequiredField() {
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
      // Set instance to required.
      'required' => TRUE,
    ];
    FieldConfig::create($field)->save();

    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();

    $url = Url::fromRoute('entity.entity_test.add_form');
    $edit = [
      'foo[0][value][date]' => '',
      'foo[0][value][time]' => '',
      'foo[0][end_value][date]' => '',
      'foo[0][end_value][time]' => '',
      'foo[0][timezone]' => 'America/Chicago',
      'foo[0][rrule]' => 'FREQ=DAILY',
    ];
    $this->drupalPostForm($url, $edit, 'Save');

    $this->assertSession()->pageTextContains('The Start date is required.');
    $this->assertSession()->pageTextNotContains('The End date is required.');
  }

  /**
   * Tests if field is set to required, only start date is required.
   *
   * End date must never be required, value is copied over from start date.
   */
  public function testHiddenTimeZoneField() {
    \Drupal::state()->set(\DATE_RECUR_BASIC_WIDGET_TEST_HIDDEN_TIMEZONE_FIELD_HOOK_FORM_ALTER, TRUE);

    $this->drupalGet(Url::fromRoute('entity.dr_entity_test.add_form'));

    // Time zone field should be hidden.
    $this->assertSession()->fieldNotExists('dr[0][timezone]');
    // Make sure something exists.
    $this->assertSession()->fieldExists('dr[0][rrule]');

    $edit = [
      // No time zone here.
      'dr[0][value][date]' => '2008-06-17',
      'dr[0][value][time]' => '12:00:00',
      'dr[0][end_value][date]' => '2008-06-17',
      'dr[0][end_value][time]' => '12:00:00',
      'dr[0][rrule]' => 'FREQ=DAILY;COUNT=10',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // The form would previously would not submit, an error was displayed.
    $this->assertSession()->pageTextContains('dr_entity_test 1 has been created.');
  }

  /**
   * Tests an error is displayed if a long RRULE is submitted.
   */
  public function testRruleMaxLengthError() {
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
        // Test a super short length.
        'rrule_max_length' => 20,
      ],
    ]);
    $field_storage->save();

    $field = [
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    $display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();

    $url = Url::fromRoute('entity.entity_test.add_form');
    $edit = [
      'foo[0][value][date]' => '2008-06-17',
      'foo[0][value][time]' => '12:00:00',
      'foo[0][end_value][date]' => '2008-06-17',
      'foo[0][end_value][time]' => '12:00:00',
      'foo[0][timezone]' => 'America/Chicago',
      'foo[0][rrule]' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
    ];
    $this->drupalPostForm($url, $edit, 'Save');

    $this->assertSession()->pageTextContains('This value is too long. It should have 20 characters or less.');
  }

  /**
   * Get last saved Dr Entity Test entity.
   *
   * @return \Drupal\date_recur_entity_test\Entity\DrEntityTest|null
   *   The entity or null if none exist.
   */
  protected function getLastSavedDrEntityTest() {
    $query = \Drupal::database()->query('SELECT MAX(id) FROM {dr_entity_test}');
    $query->execute();
    $maxId = $query->fetchField();
    return DrEntityTest::load($maxId);
  }

}
