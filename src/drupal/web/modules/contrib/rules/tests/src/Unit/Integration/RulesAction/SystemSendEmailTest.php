<?php

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Tests\rules\Unit\Integration\RulesIntegrationTestBase;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\SystemSendEmail
 * @group RulesAction
 */
class SystemSendEmailTest extends RulesIntegrationTestBase {

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Mail\MailManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $mailManager;

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
    $this->mailManager = $this->prophesize(MailManagerInterface::class);
    $this->container->set('plugin.manager.mail', $this->mailManager->reveal());

    // Mock the logger.factory service, make it return the Rules logger channel,
    // and register it in the container.
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('rules')->willReturn($this->logger->reveal());
    $this->container->set('logger.factory', $logger_factory->reveal());

    $this->action = $this->actionManager->createInstance('rules_send_email');
  }

  /**
   * Tests the summary.
   *
   * @covers ::summary
   */
  public function testSummary() {
    $this->assertEquals('Send email', $this->action->summary());
  }

  /**
   * Tests sending a mail to one recipient.
   *
   * @covers ::execute
   */
  public function testSendMailToOneRecipient() {
    $to = ['mail@example.com'];
    $this->action->setContextValue('to', $to)
      ->setContextValue('subject', 'subject')
      ->setContextValue('message', 'hello');

    $params = [
      'subject' => 'subject',
      'message' => 'hello',
    ];

    $this->mailManager->mail(
      'rules', 'rules_action_mail_' . $this->action->getPluginId(),
      implode(', ', $to),
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      $params,
      NULL
    )
      ->willReturn(['result' => TRUE])
      ->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger
      ->notice('Successfully sent email to %recipient', ['%recipient' => implode(', ', $to)])
      ->shouldHaveBeenCalled();
  }

  /**
   * Tests sending a mail to two recipients.
   *
   * @covers ::execute
   */
  public function testSendMailToTwoRecipients() {
    $to = ['mail@example.com', 'mail2@example.com'];
    $this->action->setContextValue('to', $to)
      ->setContextValue('subject', 'subject')
      ->setContextValue('message', 'hello');

    $params = [
      'subject' => 'subject',
      'message' => 'hello',
    ];

    $this->mailManager->mail(
      'rules', 'rules_action_mail_' . $this->action->getPluginId(),
      implode(', ', $to),
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      $params,
      NULL
    )
      ->willReturn(['result' => TRUE])
      ->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger
      ->notice('Successfully sent email to %recipient', ['%recipient' => implode(', ', $to)])
      ->shouldHaveBeenCalled();
  }

}
