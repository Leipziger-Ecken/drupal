<?php

namespace Drupal\htmlmail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\htmlmail\Helper\HtmlMailHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Class HtmlMailTestForm.
 *
 * @package Drupal\htmlmail\Form
 */
class HtmlMailTestForm extends FormBase {

  protected $mailManager;
  protected $accountInterface;

  const KEY_NAME = 'test';
  const DEFAULT_MAIL = 'user@example.com';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('current_user')
    );
  }

  /**
   * HtmlMailTestForm constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account service.
   */
  public function __construct(MailManagerInterface $mailManager, AccountInterface $account) {
    $this->mailManager = $mailManager;
    $this->accountInterface = $account;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'htmlmail_test';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('htmlmail.settings');

    $defaults = $config->get('htmlmail_test');
    if (empty($defaults)) {
      $defaults = [
        'to' => $config->get('site_mail') ?: self::DEFAULT_MAIL,
        'subject' => self::KEY_NAME,
        'body' => [
          'value' => self::KEY_NAME,
        ],
        'class' => HtmlMailHelper::getModuleName(),
      ];
    }

    if (empty($defaults['body']['format'])) {
      $defaults['body']['format'] = filter_default_format();
    }
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $defaults['to'],
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $defaults['subject'],
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#rows' => 20,
      '#default_value' => $defaults['body']['value'],
      '#format' => $defaults['body']['format'],
      '#required' => TRUE,
    ];

    $form['class'] = [
      '#type' => 'select',
      '#title' => $this->t('Test mail sending class'),
      '#options' => $this->getOptions(),
      '#default_value' => $defaults['class'],
      '#description' => $this->t('Select the MailSystemInterface implementation to be tested.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send test message'),
    ];

    return $form;
  }

  /**
   * Returns a list with all mail plugins.
   *
   * @return string[]
   *   List of mail plugin labels, keyed by ID.
   */
  protected function getOptions() {
    $list = [];

    // Append all MailPlugins.
    foreach ($this->mailManager->getDefinitions() as $definition) {
      $list[$definition['id']] = $definition['label'];
    }

    if (empty($list)) {
      $list['htmlmail'] = 'HTMLMailSystem';
    }

    return $list;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the form values.
    $defaults = [
      'to' => $form_state->getValue('to'),
      'subject' => $form_state->getValue('subject'),
      'body' => $form_state->getValue('body'),
      'class' => $form_state->getValue('class'),
    ];

    // Set the defaults for reuse.
    $config = $this->configFactory()->getEditable('htmlmail.settings');
    $config->set('htmlmail_test', $defaults)->save();

    // Send the email.
    $params = [
      'subject' => $defaults['subject'],
      'body' => check_markup(
        $defaults['body']['value'],
        $defaults['body']['format']
      ),
    ];

    // Send the email.
    $langcode = $this->accountInterface->getPreferredLangcode();

    $config = $this->configFactory()->getEditable('mailsystem.settings');
    $config
      ->set('defaults.sender', $defaults['class'])
      ->set('defaults.formatter', $defaults['class'])
      ->save();

    $result = $this->mailManager->mail(HtmlMailHelper::getModuleName(), self::KEY_NAME, $defaults['to'], $langcode, $params, NULL, TRUE);
    if ($result['result'] === TRUE) {
      drupal_set_message($this->t('HTML Mail test message sent.'));
    }
    else {
      drupal_set_message($this->t('Something went wrong. Please check @logs for details.', [
        '@logs' => Link::createFromRoute($this->t('logs'), 'dblog.overview')->toString(),
      ]), 'error');
    }
  }

}
