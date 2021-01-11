<?php

namespace Drupal\maillog\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for clearing all the maillog entries.
 */
class MaillogClearConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maillog_clear_log';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('All maillog database entries will be deleted. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to clear all the maillog entries?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('maillog.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    Database::getConnection('default')->truncate('maillog')->execute();
    $this->messenger()->addStatus($this->t("All maillog entries have been deleted."));
    $form_state->setRedirect('maillog.settings');
  }

}
