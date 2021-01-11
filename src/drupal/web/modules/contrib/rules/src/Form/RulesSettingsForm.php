<?php

namespace Drupal\rules\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Psr\Log\LogLevel;

/**
 * Provides rules settings form.
 */
class RulesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rules_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rules.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rules.settings');
    $form['#tree'] = TRUE;
    $form['system_log'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('System logging'),
    ];
    $form['system_log']['log_level'] = [
      '#type' => 'radios',
      '#title' => $this->t('Evaluation errors log level'),
      '#options' => [
        LogLevel::WARNING => $this->t('Log all warnings and errors'),
        LogLevel::ERROR => $this->t('Log errors only'),
      ],
      '#default_value' => $config->get('system_log.log_level'),
      '#description' => $this->t('Evaluation errors are logged to the system database logger and all other registered loggers.'),
    ];
    $form['debug_log'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debug logging'),
    ];
    $form['debug_log']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug logging'),
      '#description' => $this->t('Show debug information on screen (in the HTML response). Debug information is only shown when rules are evaluated, and is visible for users having the permission %permission.', [
        '%permission' => Link::createFromRoute('View Rules debug log', 'user.admin_permissions', [], ['fragment' => 'module-rules'])->toString(),
      ]),
      '#default_value' => $config->get('debug_log.enabled'),
    ];
    $form['debug_log']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#states' => [
        // Hide the logging destination fields when the debug log is disabled.
        'invisible' => [
          'input[name="debug_log[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['debug_log']['settings']['system_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also log debug information to the system log'),
      '#description' => $this->t('Write a copy of the debug information to the system database log. This will be visible for users having the permission %permission.', [
        '%permission' => Link::createFromRoute('View site reports', 'user.admin_permissions', [], ['fragment' => 'module-system'])->toString(),
      ]),
      '#default_value' => $config->get('debug_log.system_debug'),
    ];
    $form['debug_log']['settings']['log_level'] = [
      '#type' => 'radios',
      '#title' => $this->t('Debug log level'),
      '#options' => [
        LogLevel::DEBUG => $this->t('Log everything'),
        LogLevel::WARNING => $this->t('Log warnings and errors only'),
        LogLevel::ERROR => $this->t('Log errors only'),
      ],
      '#default_value' => $config->get('debug_log.log_level'),
      '#description' => $this->t('Level of debug log messages shown on screen'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rules.settings')
      ->set('system_log.log_level', $form_state->getValue([
        'system_log',
        'log_level',
      ]))
      ->set('debug_log.enabled', $form_state->getValue([
        'debug_log',
        'enabled',
      ]))
      ->set('debug_log.system_debug', $form_state->getValue([
        'debug_log',
        'settings',
        'system_debug',
      ]))
      ->set('debug_log.log_level', $form_state->getValue([
        'debug_log',
        'settings',
        'log_level',
      ]))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
