<?php

namespace Drupal\maillog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class MaillogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maillog_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['maillog.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maillog.settings');

    $form = [];

    $form['clear_maillog'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Clear Maillog'),
    ];

    $form['clear_maillog']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all maillog entries'),
      '#submit' => ['::clearLog'],
    ];

    $form['maillog_send'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow the e-mails to be sent.'),
      '#default_value' => $config->get('send'),
    ];

    $form['maillog_log'] = [
      '#type' => 'checkbox',
      '#title' => t('Create table entries in maillog table for each e-mail.'),
      '#default_value' => $config->get('log'),
    ];

    $form['maillog_verbose'] = [
      '#type' => 'checkbox',
      '#title' => t('Display the e-mails on page.'),
      '#default_value' => $config->get('verbose'),
      '#description' => $this->t('If enabled, anonymous users with permissions will see any verbose output mail.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maillog.settings')
      ->set('send', $form_state->getValue('maillog_send'))
      ->set('log', $form_state->getValue('maillog_log'))
      ->set('verbose', $form_state->getValue('maillog_verbose'))->save();

    parent::submitForm($form, $form_state);

    if ($this->config('maillog.settings')->get('verbose') == TRUE) {
      $this->messenger()->addWarning($this->t('Any user having the permission "view maillog" will see output of any mail that is sent.'));
    }
  }

  /**
   * Clear all the maillog entries.
   */
  public function clearLog(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('maillog.clear_log');
  }

}
