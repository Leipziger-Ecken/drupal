<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that the Rules UI pages are reachable.
 *
 * @group RulesUi
 */
class UiPageTest extends RulesBrowserTestBase {

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
   * Tests that the reaction rule listing page works.
   */
  public function testReactionRulePage() {
    $account = $this->drupalCreateUser(['administer rules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/workflow/rules');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);

    // Test that there is an empty reaction rule listing.
    $assert->pageTextContains('There are no enabled reaction rules.');
  }

  /**
   * Tests that creating a reaction rule works.
   */
  public function testCreateReactionRule() {
    $account = $this->drupalCreateUser(['administer rules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Add reaction rule');

    $this->fillField('Label', 'Test rule');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_insert:node');

    $this->pressButton('Save');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Reaction rule Test rule has been created.');

    $this->clickLink('Add condition');

    $this->fillField('Condition', 'rules_node_is_promoted');
    $this->pressButton('Continue');

    $this->fillField('context_definitions[node][setting]', 'node');
    $this->pressButton('Save');

    $assert->statusCodeEquals(200);
    $assert->pageTextContains('You have unsaved changes.');

    $this->pressButton('Save');
    $assert->pageTextContains('Reaction rule Test rule has been updated. ');
  }

  /**
   * Tests that enabling and disabling a rule works.
   */
  public function testRuleStatusOperations() {
    // Setup an active rule.
    $this->testCreateReactionRule();
    $this->drupalGet('admin/config/workflow/rules');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test disabling.
    $this->clickLink('Disable');
    $assert->pageTextContains('The reaction rule Test rule has been disabled.');

    // Test enabling.
    $this->clickLink('Enable');
    $assert->pageTextContains('The reaction rule Test rule has been enabled.');
  }

  /**
   * Tests that cancelling an expression from a rule works.
   */
  public function testCancelExpressionInRule() {
    // Setup a rule with one condition.
    $this->testCreateReactionRule();

    $this->clickLink('Add condition');
    $this->fillField('Condition', 'rules_node_is_promoted');
    $this->pressButton('Continue');

    $this->fillField('context_definitions[node][setting]', 'node');
    $this->pressButton('Save');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->pageTextContains('You have unsaved changes.');

    // Edit and cancel.
    $this->pressButton('Cancel');
    $assert->pageTextContains('Canceled.');

    // Make sure that we are back at the overview listing page.
    $this->assertEquals(1, preg_match('#/admin/config/workflow/rules$#', $this->getSession()->getCurrentUrl()));
  }

  /**
   * Tests that deleting an expression from a rule works.
   */
  public function testDeleteExpressionInRule() {
    // Setup a rule with one condition.
    $this->testCreateReactionRule();

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->clickLink('Delete');
    $assert->pageTextContains('Are you sure you want to delete Node is promoted from Test rule?');

    $this->pressButton('Delete');
    $assert->pageTextContains('You have unsaved changes.');

    $this->pressButton('Save');
    $assert->pageTextContains('Reaction rule Test rule has been updated. ');
  }

  /**
   * Tests that a condition with no context can be configured.
   */
  public function testNoContextCondition() {
    // Setup a rule with one condition.
    $this->testCreateReactionRule();

    $this->clickLink('Add condition');
    // The rules_test_true condition does not define context in its annotation.
    $this->fillField('Condition', 'rules_test_true');
    $this->pressButton('Continue');
    // Pressing 'Save' will generate an exception and the test will fail if
    // Rules does not support conditions without a context.
    // Exception: Warning: Invalid argument supplied for foreach().
    $this->pressButton('Save');
  }

  /**
   * Tests that a negated condition has NOT prefixed to its label.
   */
  public function testNegatedCondition() {
    // Setup a rule with one condition.
    $this->testCreateReactionRule();

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    // Check that the label shows up on the Rule edit page.
    $assert->pageTextContains('Node is promoted');

    // Edit the condition, negate it, then check the label again.
    $this->clickLink('Edit');
    $this->fillField('Negate', 1);
    $this->pressButton('Save');
    $assert->pageTextContains('NOT Node is promoted');
  }

  /**
   * Tests that an action with a multiple context can be configured.
   */
  public function testMultipleContextAction() {
    $account = $this->drupalCreateUser(['administer rules']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/workflow/rules');
    $this->clickLink('Add reaction rule');

    $this->fillField('Label', 'Test rule');
    $this->fillField('Machine-readable name', 'test_rule');
    $this->fillField('React on event', 'rules_entity_insert:node');

    $this->pressButton('Save');

    $this->clickLink('Add action');
    $this->fillField('Action', 'rules_send_email');
    $this->pressButton('Continue');

    // Push the data selection switch 2 times to make sure that also works and
    // does not throw PHP notices.
    $this->pressButton('Switch to data selection');
    $this->pressButton('Switch to the direct input mode');

    $this->fillField('context_definitions[to][setting]', 'klausi@example.com');
    $this->fillField('context_definitions[subject][setting]', 'subject');
    $this->fillField('context_definitions[message][setting]', 'message');
    $this->pressButton('Save');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
  }

}
