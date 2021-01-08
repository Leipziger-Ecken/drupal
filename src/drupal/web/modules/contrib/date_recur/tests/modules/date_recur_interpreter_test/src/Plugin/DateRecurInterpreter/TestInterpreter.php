<?php

namespace Drupal\date_recur_interpreter_test\Plugin\DateRecurInterpreter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\date_recur\Plugin\DateRecurInterpreterPluginBase;

/**
 * Provides an interpreter for testing.
 *
 * @DateRecurInterpreter(
 *  id = "test_interpreter",
 *  label = @Translation("Testing interpreter"),
 * )
 *
 * @ingroup RLanvinPhpRrule
 */
class TestInterpreter extends DateRecurInterpreterPluginBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'show_foo' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function interpret(array $rules, string $language, ?\DateTimeZone $timeZone = NULL): string {
    $pluginConfig = $this->getConfiguration();

    if ($pluginConfig['show_foo']) {
      return 'foo';
    }
    else {
      return 'bar';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['show_foo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the foo'),
      '#default_value' => $this->configuration['show_foo'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show_foo'] = $form_state->getValue('show_foo');
  }

  /**
   * {@inheritdoc}
   */
  public function supportedLanguages(): array {
    return [
      'es',
    ];
  }

}
