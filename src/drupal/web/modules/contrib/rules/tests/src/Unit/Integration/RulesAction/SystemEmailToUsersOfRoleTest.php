<?php

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Tests\rules\Unit\Integration\RulesEntityIntegrationTestBase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\SystemEmailToUsersOfRole
 * @group RulesAction
 */
class SystemEmailToUsersOfRoleTest extends RulesEntityIntegrationTestBase {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $mailManager;

  /**
   * @var \Drupal\user\UserInterface[]|\Prophecy\Prophecy\ProphecyInterface[]
   */
  protected $accounts;
  /**
   * @var \Drupal\user\UserStorageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $userStorage;

  /**
   * The action to be tested.
   *
   * @var \Drupal\rules\Plugin\RulesAction\SystemSendEmail
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->enableModule('user');

    // Mock the logger.factory service, make it return the Rules logger channel,
    // and register it in the container.
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('rules')->willReturn($this->logger->reveal());
    $this->container->set('logger.factory', $logger_factory->reveal());

    $this->mailManager = $this->prophesize(MailManagerInterface::class);
    $this->container->set('plugin.manager.mail', $this->mailManager->reveal());

    // Create an array of dummy users with the 'recipient' role.
    $this->accounts = [];
    for ($i = 0; $i < 3; $i++) {
      $account = $this->prophesizeEntity(UserInterface::class);
      $account->getPreferredLangcode()
        ->willReturn('site_default');
      $account->getEmail()
        ->willReturn('user' . $i . '@example.com');
      $account->addRole('recipient');
      // Add the 'moderator' role to only the first account.
      if ($i == 0) {
        $account->addRole('moderator');
      }
      $this->accounts[] = $account->reveal();
    }

    // Create dummy user storage object.
    $this->userStorage = $this->prophesize(UserStorageInterface::class);
    $this->entityTypeManager->getStorage('user')
      ->willReturn($this->userStorage->reveal());

    $this->action = $this->actionManager->createInstance('rules_email_to_users_of_role');
  }

  /**
   * Tests the summary.
   *
   * @covers ::summary
   */
  public function testSummary() {
    $this->assertEquals('Send email to all users of a role', $this->action->summary());
  }

  /**
   * Tests sending an email to one role.
   *
   * @covers ::execute
   */
  public function testSendMailToOneRoles() {
    // Mock the 'recipient' user role.
    $recipient = $this->prophesize(RoleInterface::class);
    $recipient->id()->willReturn('recipient');

    $roles = [$recipient->reveal()];
    $this->action->setContextValue('roles', $roles)
      ->setContextValue('subject', 'subject')
      ->setContextValue('message', 'hello');

    $params = [
      'subject' => 'subject',
      'message' => 'hello',
    ];

    $rids = ['recipient'];
    $this->userStorage->loadByProperties(['roles' => $rids, 'status' => 1])
      ->willReturn($this->accounts);

    $this->mailManager->mail(
      'rules', 'rules_action_mail_' . $this->action->getPluginId(),
      Argument::any(),
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      $params,
      NULL
    )
      ->willReturn(['result' => TRUE])
      ->shouldBeCalledTimes(3);

    $this->action->execute();

    $this->logger->notice('Successfully sent email to %number out of %count users having the role(s) %roles', [
      '%number' => 3,
      '%count' => count($this->accounts),
      '%roles' => implode(', ', $rids),
    ])->shouldHaveBeenCalled();
  }

  /**
   * Tests sending an email to two roles.
   *
   * @covers ::execute
   */
  public function testSendMailToTwoRoles() {
    // Mock the 'recipient' and 'moderator' roles.
    $recipient = $this->prophesize(RoleInterface::class);
    $recipient->id()->willReturn('recipient');
    $moderator = $this->prophesize(RoleInterface::class);
    $moderator->id()->willReturn('moderator');

    $roles = [$recipient->reveal(), $moderator->reveal()];
    $this->action->setContextValue('roles', $roles)
      ->setContextValue('subject', 'subject')
      ->setContextValue('message', 'hello');

    $params = [
      'subject' => 'subject',
      'message' => 'hello',
    ];

    $rids = ['recipient', 'moderator'];
    $this->userStorage->loadByProperties(['roles' => $rids, 'status' => 1])
      ->willReturn([$this->accounts[0]]);

    $this->mailManager->mail(
      'rules', 'rules_action_mail_' . $this->action->getPluginId(),
      Argument::any(),
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      $params,
      NULL
    )
      ->willReturn(['result' => TRUE])
      ->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger->notice('Successfully sent email to %number out of %count users having the role(s) %roles', [
      '%number' => 1,
      '%count' => 1,
      '%roles' => implode(', ', $rids),
    ])->shouldHaveBeenCalled();
  }

}
