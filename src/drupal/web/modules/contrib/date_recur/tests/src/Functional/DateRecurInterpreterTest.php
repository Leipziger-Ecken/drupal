<?php

namespace Drupal\Tests\date_recur\Functional;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests interfaces for interpreters.
 *
 * @group date_recur
 */
class DateRecurInterpreterTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'date_recur_interpreter_test',
    'date_recur',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'date_recur manage interpreters',
    ]));
  }

  /**
   * Tests adding a new interpreter.
   */
  public function testInterpreterWebCreate() {
    $instanceLabel = 'Kaya';
    $url = Url::fromRoute('entity.date_recur_interpreter.add_form');
    $this->drupalGet($url);
    $buttonLabel = \t('Next');
    $this->assertSession()->buttonExists($buttonLabel);
    $this->assertSession()->pageTextContains('Add interpreter');
    $this->assertSession()->optionExists('plugin_type', 'test_interpreter');
    $page = $this->getSession()->getPage();
    $page->findField('label')->setValue($instanceLabel);
    $this->assertSession()->waitForElementVisible('css', '[name="label"] + * .machine-name-value');
    $page->findField('plugin_type')->setValue('test_interpreter');
    $this->submitForm([], $buttonLabel, 'date-recur-interpreter-add-form');

    // Page should have reloaded, a different submit button visible.
    $this->assertSession()->buttonNotExists($buttonLabel);
    $buttonLabel = \t('Save');
    $this->assertSession()->pageTextContains('Add interpreter');
    $this->assertSession()->checkboxNotChecked('configure[show_foo]');
    $page = $this->getSession()->getPage();
    $page->checkField('configure[show_foo]');
    $this->submitForm([], $buttonLabel, 'date-recur-interpreter-add-form');

    // Page reloaded to interpreter collection page, message displayed.
    $this->assertSession()->addressEquals(Url::fromRoute('entity.date_recur_interpreter.collection')->setAbsolute()->toString());
    $this->assertSession()->elementTextContains('css', '.messages', 'Saved the ' . $instanceLabel . ' interpreter.');
  }

}
