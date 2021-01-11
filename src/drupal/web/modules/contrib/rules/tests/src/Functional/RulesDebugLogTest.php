<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that the Rules UI pages are reachable.
 *
 * @group RulesUi
 */
class RulesDebugLogTest extends RulesBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rules', 'rules_test'];

  /**
   * We use the minimal profile because we want to test local action links.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Testing profile doesn't include a 'page' content type.
    // We will need this to test bundle-specific entity CRUD events.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Turn on debug logging.
    $this->config('rules.settings')
      ->set('debug_log.enabled', TRUE)
      ->set('debug_log.log_level', 'debug')
      ->save();
  }

  /**
   * Tests that entity CRUD events get fired only once.
   */
  public function testEventDebugLogMessage() {
    // Create a user who can see the rules debug logs.
    $account = $this->createUser([
      'administer rules',
      'access rules debug',
      'create page content',
    ]);
    $this->drupalLogin($account);

    // Create a Rule which we can trigger.
    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Add reaction rule');

    $this->fillField('Label', 'Test rule');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_insert:node');

    $this->pressButton('Save');

    // Add a new page, which should trigger the above Rule.
    $this->drupalGet('node/add/page');
    $this->fillField('Title', 'Test page');
    $this->pressButton('Save');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Ensure that one and only one event message appears.
    $assert->pageTextContainsOnce('0 ms Reacting on event After saving a new content item.');
  }

}
