<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Functional test for the embedded Rules example implementation.
 *
 * @group RulesUi
 */
class RulesUiEmbedTest extends RulesBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rules_test_ui_embed'];

  /**
   * @covers \Drupal\rules_test_ui_embed\Form\SettingsForm
   */
  public function testExampleUi() {
    $account = $this->drupalCreateUser([
      'administer rules',
      'access administration pages',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/user-interface');

    $this->drupalGet('admin/config/user-interface/css');
    $this->clickLink('Add condition');
    $this->fillField('Condition', 'rules_data_comparison');
    $this->pressButton('Continue');
    $this->fillField('context_definitions[data][setting]', '@user.current_user_context:current_user.uid.value');
    $this->fillField('context_definitions[value][setting]', '234');
    $this->pressButton('Save');

    // Now the condition should be listed. Try editing it.
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $assert->pageTextContains('Data comparison');
    $this->clickLink('Edit');
    $assert->fieldValueEquals('context_definitions[data][setting]', '@user.current_user_context:current_user.uid.value');
    $assert->fieldValueEquals('context_definitions[value][setting]', '234');
    $this->fillField('context_definitions[value][setting]', '123');
    $this->pressButton('Save');
    $assert->pageTextContains('Data comparison');

    // One more save, with the values we want, to permanently store the changes.
    $this->fillField('css_file', 'css/test2.css');
    $this->pressButton('Save configuration');
    $assert->pageTextContains('The configuration options have been saved.');

    // Reload and ensure data is still there.
    $this->drupalGet('admin/config/user-interface/css');
    $assert->fieldValueEquals('css_file', 'css/test2.css');
    $assert->pageTextContains('Data comparison');

    // Delete condition and save.
    $this->clickLink('Delete');
    $this->pressButton('Delete');
    $this->pressButton('Save configuration');
    $assert->pageTextContains('The configuration options have been saved.');
    $assert->pageTextNotContains('Data comparison');
  }

}
