<?php

namespace Drupal\Tests\maillog\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the maillog plugin.
 *
 * @group maillog
 */
class MailTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'maillog',
    'maillog_test',
    'user',
    'system',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('maillog', 'maillog');
    $this->installConfig(['system', 'maillog']);
    // The system.site.mail setting goes into the From header of outgoing mails.
    $this->config('system.site')->set('mail', 'simpletest@example.com')->save();

    // Use the maillog mail plugin.
    $GLOBALS['config']['system.mail']['interface']['default'] = 'maillog';

    // Disables E-Mail Sending, only tracking.
    $this->config('maillog.settings')
      ->set('send', FALSE)
      ->save();
  }

  /**
   * Tests logging mail with maillog module.
   */
  public function testLogging() {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Send an email.
    $from_email = 'simpletest@example.com';
    $reply_email = 'someone_else@example.com';
    \Drupal::service('plugin.manager.mail')->mail('simpletest', 'from_test', 'from_test@example.com', $language, [], $reply_email);

    // Compare the maillog db entry with the sent mail.
    $logged_email = $this->getLatestMaillogEntry();
    $this->assertTrue(is_array($logged_email), 'Email is captured.');
    $this->assertEqual($from_email, $logged_email['header_from'], 'Email is sent correctly.');
    $this->assertEqual($reply_email, $logged_email['header_all']['Reply-to'], 'Message is sent with correct reply address.');
  }

  /**
   * Gets the latest Maillog entry.
   *
   * @return array
   *   Maillog entry.
   */
  protected function getLatestMaillogEntry() {
    $result = \Drupal::database()->queryRange('SELECT id, header_from, header_to, header_reply_to, header_all, subject, body
      FROM {maillog}
      ORDER BY id DESC', 0, 1);

    if ($maillog = $result->fetchAssoc()) {
      // Unserialize values.
      $maillog['header_all'] = unserialize($maillog['header_all']);
    }
    return $maillog;
  }

  /**
   * Confirm what happens with long subject lines.
   */
  public function testLongSubject() {
    // Send an email.
    $from_email = 'from_test@example.com';
    $to_email = 'to_test@example.com';
    \Drupal::service('plugin.manager.mail')->mail('maillog_test', 'maillog_long_subject_test', $to_email, 'en', [], $from_email);

    // Compare the maillog db entry with the sent mail.
    $logged_email = $this->getLatestMaillogEntry();
    $this->assertTrue(!empty($logged_email), 'The test email was captured.');

    // The original subject line, as copied from maillog_test_mail().
    $subject = str_repeat('Test email subject ', 20);

    // Duplicate the string trimming.
    $subject_trimmed = mb_substr($subject, 0, 255);
    self::assertEquals($subject_trimmed, $logged_email['subject'], 'Email subject was trimmed correctly.');
    self::assertNotEquals($subject, $logged_email['subject'], 'Email subject is not untrimmed.');
  }

}
