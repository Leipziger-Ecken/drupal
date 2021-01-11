<?php

namespace Drupal\usernotification\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Sends a Mail to a user.
 *
 * @Action(
 *   id = "usernotification_action",
 *   label = @Translation("Send User Notification Message"),
 *   type = "user"
 * )
 */
class SendUserNotification extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a EmailAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messanger.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account) {
      $to = $account->getEmail();
      $site_config = $this->configFactory->get('system.site');
      $site_mail = $site_config->get('mail');
      $langcode = $account->getPreferredLangcode();
      $params = [
        'headers' => [
          'Content-Type' => 'text/html; charset=UTF-8;',
          'Content-Transfer-Encoding' => '8Bit',
          'MIME-Version' => '1.0',
          'reply-to' => $site_mail,
        ],
        'from' => $site_mail,
        'account' => $account,
      ];
      $module = 'usernotification';
      $key = 'user_notification_email';
      $send = TRUE;
      $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }
    else {
      $this->messenger->addMessage(t('There is some problem in sending the user notification for @user_name. Contact to support!', ['@user_name' => $account->getUsername()]), 'error');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
