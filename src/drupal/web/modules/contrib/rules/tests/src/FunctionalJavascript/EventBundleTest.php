<?php

namespace Drupal\Tests\rules\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the Ajax behavior of the Add Reaction Rule UI.
 *
 * @group RulesUi
 */
class EventBundleTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rules', 'taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Testing profile doesn't include a 'page' or 'article' content type.
    // We will need these to test bundle-specific entity CRUD events.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
  }

  /**
   * Tests that event bundle selection Ajax works.
   */
  public function testEventBundleSelection() {
    // A user who can create rules in the UI.
    $account = $this->createUser(['administer rules']);
    $this->drupalLogin($account);

    // Create a Rule which we can trigger.
    $this->drupalGet('admin/config/workflow/rules/reactions/add');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->pageTextContains('Event selection');
    $assert->pageTextContains('React on event');

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Test bundle selection Ajax rule');
    // The machine name field should be automatically filled via Ajax.
    $assert->assertWaitOnAjaxRequest();

    // Select the "After saving a new taxonomy term" event.
    $page->findField('events[0][event_name]')->selectOption('rules_entity_insert:taxonomy_term');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Restrict by type');

    // Check that the "Not selected" text is shown.
    $field = $page->findField('bundle');
    $this->assertNotEmpty($field);
    $this->assertEquals('notselected', $field->getValue());

    // Select the "Cron maintenance tasks are performed" event.
    $page->findField('events[0][event_name]')->selectOption('rules_system_cron');
    $assert->assertWaitOnAjaxRequest();
    // This event doesn't have any bundles, so the additional selection that
    // was presented above to restrict by type should now be hidden.
    $assert->pageTextNotContains('Restrict by type');
    $field = $page->findField('bundle');
    $this->assertEmpty($field);

    // Select the "After saving a new content item" event.
    $page->findField('events[0][event_name]')->selectOption('rules_entity_insert:node');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Restrict by type');

    $field = $page->findField('events[0][event_name]');
    $this->assertNotEmpty($field);
    $this->assertEquals('rules_entity_insert:node', $field->getValue());
    $assert->assertWaitOnAjaxRequest();

    // Don't try to set the bundle unless the event has bundles!
    if ($page->findField('bundle')) {
      // Check to see that our "page" content type is an option.
      $page->findField('bundle')->selectOption('page');
      $assert->assertWaitOnAjaxRequest();
      $field = $page->findField('bundle');
      $this->assertNotEmpty($field);
      $this->assertEquals('page', $field->getValue());

      // Now check our "article" type, and leave it selected.
      $page->findField('bundle')->selectOption('article');
      $assert->assertWaitOnAjaxRequest();
      $field = $page->findField('bundle');
      $this->assertNotEmpty($field);
      $this->assertEquals('article', $field->getValue());
    }
    else {
      // If we reach this point, $page->findField('bundle') is FALSE, so there
      // should be no bundles and the bundle select should be hidden.
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
      $assert->pageTextNotContains('Restrict by type');
      $assert->assert(empty($bundles), 'Restrict by type field is not shown and there are no bundles.');
    }

    // Save the Reaction Rule with event "rules_entity_insert:node--article".
    $page->pressButton('Save');

    // Now ensure the bundle we selected with Ajax got saved.
    $this->drupalGet('admin/config/workflow/rules');
    $assert->pageTextContains('Test bundle selection Ajax rule');
    $assert->pageTextContains('After saving a new content item of type Article');
    $assert->pageTextContains('Machine name: test_bundle_selection_ajax_rule');

    // And ensure the qualified event name is displayed properly in the UI.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/test_bundle_selection_ajax_rule');
    $assert->pageTextContains('After saving a new content item of type Article');
    $assert->pageTextContains('Machine name: rules_entity_insert:node--article');
  }

}
