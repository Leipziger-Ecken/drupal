<?php

namespace Drupal\http_client_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HttpClientManagerConfigForm.
 *
 * @package Drupal\http_client_manager\Form
 */
class HttpClientManagerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'http_client_manager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'http_client_manager_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('http_client_manager.settings');
    $form['enable_overriding_service_definitions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable overriding of HTTP Services API definitions'),
      '#description' => $this->t('Check this option to enable overriding of HTTP Services API definitions via settings.php.'),
      '#default_value' => $config->get('enable_overriding_service_definitions'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('http_client_manager.settings')
      ->set('enable_overriding_service_definitions', $form_state->getValue('enable_overriding_service_definitions'))
      ->save();
  }

}
