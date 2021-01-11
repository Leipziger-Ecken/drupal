<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Send account email' action.
 *
 * @RulesAction(
 *   id = "rules_send_account_email",
 *   label = @Translation("Send account email"),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User"),
 *       description = @Translation("The user to whom we send the email.")
 *     ),
 *     "email_type" = @ContextDefinition("string",
 *       label = @Translation("Email type"),
 *       description = @Translation("The type of the email to send.")
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class SendAccountEmail extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a SendAccountEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The rules logger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules')
    );
  }

  /**
   * Send account email.
   *
   * @param \Drupal\user\UserInterface $user
   *   User who should receive the notification.
   * @param string $email_type
   *   Type of email to be sent.
   */
  protected function doExecute(UserInterface $user, $email_type) {
    $message = _user_mail_notify($email_type, $user);

    // Log the success or failure.
    if (!$message['result']) {
      $this->logger->notice('%type email sent to %recipient.', [
        '%type' => $email_type,
        '%recipient' => $user->mail,
      ]);
    }
    else {
      $this->logger->error('Failed to send %type email to %recipient.', [
        '%type' => $email_type,
        '%recipient' => $user->mail,
      ]);
    }
  }

}
