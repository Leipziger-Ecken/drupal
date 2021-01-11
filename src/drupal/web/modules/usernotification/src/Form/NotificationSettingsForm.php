<?php

namespace Drupal\usernotification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for performing a 1-click site backup.
 */
class NotificationSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usernotification_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'usernotification.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('usernotification.settings');
    $message_body = $config->get('message');

    $form['notification_fieldset'] = [
      '#type' => 'fieldset',
      "#title" => $this->t("Notification Setting"),
      "#collapsible" => FALSE,
      "#collapsed" => FALSE,
      "#tree" => FALSE,
    ];
    $form['notification_fieldset']['subject'] = [
      '#type' => 'textfield',
      "#title" => $this->t('Subject'),
      '#default_value' => !empty($config->get('subject')) ? $config->get('subject') : '',
      '#required' => TRUE,
    ];
    $form['notification_fieldset']['message'] = [
      '#type' => 'text_format',
      "#title" => $this->t('Notification message'),
      '#description' => $this->t("Configure to send notification message that will send to user from people's page."),
      '#default_value' => !empty($config->get('message')) ? $message_body['value'] : '',
      '#required' => TRUE,
      '#rows' => 15,
      '#format' => !empty($config->get('message')) ? $message_body['format'] : filter_default_format(),
    ];
    $form['notification_fieldset']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'site'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#weight' => 1,
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory->getEditable('usernotification.settings')
      ->set('message', $values['message'])
      ->set('subject', $values['subject'])
      ->save();
  }

}
