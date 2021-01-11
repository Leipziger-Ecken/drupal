<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Email to users of a role' action.
 *
 * @RulesAction(
 *   id = "rules_email_to_users_of_role",
 *   label = @Translation("Send email to all users of a role"),
 *   category = @Translation("System"),
 *   context_definitions = {
 *     "roles" = @ContextDefinition("entity:user_role",
 *       label = @Translation("Roles"),
 *       description = @Translation("The roles to which to send the email."),
 *       multiple = TRUE
 *     ),
 *     "subject" = @ContextDefinition("string",
 *       label = @Translation("Subject"),
 *       description = @Translation("The email's subject.")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body. Drupal will by default remove all HTML tags. If you want to use HTML you must override this behavior by installing a contributed module such as Mime Mail.")
 *     ),
 *     "reply" = @ContextDefinition("email",
 *       label = @Translation("Reply to"),
 *       description = @Translation("The email's reply-to address. Leave it empty to use the site-wide configured address."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *     "language" = @ContextDefinition("language",
 *       label = @Translation("Language"),
 *       description = @Translation("If specified, the language object (not language code) used for getting the email message and subject."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class SystemEmailToUsersOfRole extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a SystemEmailToUsersOfRole object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The rules logger service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\user\UserStorageInterface $userStorage
   *   The user storage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelInterface $logger, MailManagerInterface $mail_manager, UserStorageInterface $userStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->userStorage = $userStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules'),
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * Sends an email to all users of the specified role(s).
   *
   * @param \Drupal\user\RoleInterface[] $roles
   *   Array of user roles.
   * @param string $subject
   *   Subject of the email.
   * @param string $message
   *   Email message text.
   * @param string $reply
   *   (optional) Reply to email address.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   (optional) Language object. If not specified, email will be sent to each
   *   recipient in the recipient's preferred language.
   */
  protected function doExecute(array $roles, $subject, $message, $reply = NULL, LanguageInterface $language = NULL) {
    if (empty($roles)) {
      return;
    }
    $rids = array_map(function ($role) {
      return $role->id();
    }, $roles);

    // Set a unique key for this email.
    $key = 'rules_action_mail_' . $this->getPluginId();

    // Select only active users, based on the roles given. We do not want to
    // send email to blocked users.
    $accounts = $this->userStorage->loadByProperties([
      'roles' => $rids,
      'status' => 1,
    ]);

    $params = [
      'subject' => $subject,
      'message' => $message,
    ];

    // Loop over users and send email to each individually using that user's
    // preferred language (or a fixed language, if passed in the context).
    $number = 0;
    foreach ($accounts as $account) {
      // Language to use. Value passed in the context takes precedence.
      $langcode = isset($language) ? $language->getId() : $account->getPreferredLangcode();

      $message = $this->mailManager->mail('rules', $key, $account->getEmail(), $langcode, $params, NULL);
      $number += $message['result'] ? 1 : 0;
    }
    $this->logger->notice('Successfully sent email to %number out of %count users having the role(s) %roles', [
      '%number' => $number,
      '%count' => count($accounts),
      '%roles' => implode(', ', $rids),
    ]);
  }

}
