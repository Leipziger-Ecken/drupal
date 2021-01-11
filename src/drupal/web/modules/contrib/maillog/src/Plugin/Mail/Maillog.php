<?php

namespace Drupal\maillog\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Mail(
 *   id = "maillog",
 *   label = @Translation("Maillog Mail-Plugin"),
 *   description = @Translation("Maillog mail plugin for sending and formating complete mails.")
 * )
 */
class Maillog implements MailInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $default = new PhpMail();
    return $default->format($message);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $config = \Drupal::configFactory()->get('maillog.settings');
    // Log the e-mail.
    if ($config->get('log')) {
      $record = new \stdClass();

      // In case the subject/from/to is already encoded, decode with
      // Unicode::mimeHeaderDecode().
      $record->header_message_id = isset($message['headers']['Message-ID']) ? $message['headers']['Message-ID'] : $this->t('Not delivered');
      $record->subject = $message['subject'];
      $record->subject = mb_substr(Unicode::mimeHeaderDecode($record->subject), 0, 255);
      $record->body = $message['body'];
      $record->header_from = isset($message['from']) ? $message['from'] : NULL;
      $record->header_from = Unicode::mimeHeaderDecode($record->header_from);

      $header_to = [];
      if (isset($message['to'])) {
        if (is_array($message['to'])) {
          foreach ($message['to'] as $value) {
            $header_to[] = Unicode::mimeHeaderDecode($value);
          }
        }
        else {
          $header_to[] = Unicode::mimeHeaderDecode($message['to']);
        }
      }
      $record->header_to = implode(', ', $header_to);

      $record->header_reply_to = isset($message['headers']['Reply-To']) ? $message['headers']['Reply-To'] : '';
      $record->header_all = serialize($message['headers']);
      $record->sent_date = \Drupal::time()->getRequestTime();

      Database::getConnection()->insert('maillog')
        ->fields((array) $record)
        ->execute();
    }

    // Display the email if the verbose is enabled.
    if ($config->get('verbose') && \Drupal::currentUser()->hasPermission('view maillog')) {

      // Print the message.
      $header_output = print_r($message['headers'], TRUE);
      $output = $this->t('A mail has been sent: <br/> [Subject] => @subject <br/> [From] => @from <br/> [To] => @to <br/> [Reply-To] => @reply <br/> <pre>  [Header] => @header <br/> [Body] => @body </pre>', [
        '@subject' => $message['subject'],
        '@from' => $message['from'],
        '@to' => $message['to'],
        '@reply' => isset($message['reply_to']) ? $message['reply_to'] : NULL,
        '@header' => $header_output,
        '@body' => $message['body'],
      ]);
      \Drupal::messenger()->addStatus($output, TRUE);
    }

    if ($config->get('send')) {
      $default = new PhpMail();
      $result = $default->mail($message);
    }
    elseif (\Drupal::currentUser()->hasPermission('administer maillog')) {
      $message = $this->t('Sending of e-mail messages is disabled by Maillog module. Go @here to enable.', ['@here' => \Drupal::service('link_generator')->generate('here', Url::fromRoute('maillog.settings'))]);

      \Drupal::messenger()->addWarning($message, TRUE);
    }
    else {
      \Drupal::logger('maillog')->notice('Attempted to send an email, but sending emails is disabled.');
    }
    return isset($result) ? $result : TRUE;
  }

}
