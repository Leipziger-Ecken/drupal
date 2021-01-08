<?php

namespace Drupal\Tests\date_recur\Functional;

use Drupal\Core\Url;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests date recur field settings form.
 *
 * @group date_recur
 */
class DateRecurFieldSettingsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field_ui',
    'field',
    'user',
    'system',
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

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $field = [
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    $this->fieldConfig = FieldConfig::create($field);
    $this->fieldConfig->save();

    $user = $this->createUser(['administer entity_test fields']);
    $this->drupalLogin($user);
  }

  /**
   * Tests field config when all frequencies are enabled.
   */
  public function testAllAllowed() {
    $url = Url::fromRoute('entity.field_config.entity_test_field_edit_form', [
      'bundle' => 'entity_test',
      'field_config' => $this->fieldConfig->id(),
    ]);
    $this->drupalGet($url);

    $this->submitForm([
      'settings[parts][all]' => TRUE,
    ], 'Save settings');
    $this->assertSession()->pageTextContains('Saved foo configuration.');

    $this->assertEquals([
      'all' => TRUE,
      'frequencies' => [
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ], $this->getPartSettings());
  }

  /**
   * Tests field config when all parts are disabled for a frequency.
   */
  public function testAllDisabled() {
    $url = Url::fromRoute('entity.field_config.entity_test_field_edit_form', [
      'bundle' => 'entity_test',
      'field_config' => $this->fieldConfig->id(),
    ]);
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();
    $page->uncheckField('settings[parts][all]');

    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains('Saved foo configuration.');

    $this->assertEquals([
      'all' => FALSE,
      'frequencies' => [
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => [],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ], $this->getPartSettings());
  }

  /**
   * Tests field config when all parts are enabled for a frequency.
   */
  public function testAllPartsInFrequency() {
    $url = Url::fromRoute('entity.field_config.entity_test_field_edit_form', [
      'bundle' => 'entity_test',
      'field_config' => $this->fieldConfig->id(),
    ]);
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();
    $page->uncheckField('settings[parts][all]');

    $this->submitForm([
      // Check the 'all-parts' radio in weekly.
      'settings[parts][table][WEEKLY][setting]' => 'all-parts',
    ], 'Save settings');
    $this->assertSession()->pageTextContains('Saved foo configuration.');

    $this->assertEquals([
      'all' => FALSE,
      'frequencies' => [
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => ['*'],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ], $this->getPartSettings());
  }

  /**
   * Tests field config when some parts are enabled for a frequency.
   */
  public function testSomePartsInFrequency() {
    $url = Url::fromRoute('entity.field_config.entity_test_field_edit_form', [
      'bundle' => 'entity_test',
      'field_config' => $this->fieldConfig->id(),
    ]);
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();
    $page->uncheckField('settings[parts][all]');

    // Check the 'some-parts' radio in weekly.
    $this->assertSession()
      ->fieldExists('settings[parts][table][WEEKLY][setting]')
      ->setValue('some-parts');

    $page->checkField('settings[parts][table][WEEKLY][parts][COUNT]');
    $page->checkField('settings[parts][table][WEEKLY][parts][DTSTART]');
    $page->checkField('settings[parts][table][WEEKLY][parts][BYDAY]');
    $page->checkField('settings[parts][table][WEEKLY][parts][BYSETPOS]');

    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains('Saved foo configuration.');

    $this->assertEquals([
      'all' => FALSE,
      'frequencies' => [
        'SECONDLY' => [],
        'MINUTELY' => [],
        'HOURLY' => [],
        'DAILY' => [],
        'WEEKLY' => ['BYDAY', 'BYSETPOS', 'COUNT', 'DTSTART'],
        'MONTHLY' => [],
        'YEARLY' => [],
      ],
    ], $this->getPartSettings());
  }

  /**
   * Gets parts setting from the field config.
   *
   * @return array
   *   An array of parts settings.
   */
  protected function getPartSettings() {
    $fieldConfig = FieldConfig::load($this->fieldConfig->id());
    return $fieldConfig->getSetting('parts');
  }

}
