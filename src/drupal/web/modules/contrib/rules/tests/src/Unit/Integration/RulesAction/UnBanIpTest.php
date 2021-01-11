<?php

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Tests\rules\Unit\Integration\RulesIntegrationTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\UnBanIp
 * @group RulesAction
 */
class UnBanIpTest extends RulesIntegrationTestBase {

  /**
   * The action to be tested.
   *
   * @var \Drupal\rules\Core\RulesActionInterface
   */
  protected $action;

  /**
   * @var \Drupal\ban\BanIpManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $banManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $request;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Must enable the ban module.
    $this->enableModule('ban');
    $this->banManager = $this->prophesize(BanIpManagerInterface::class);
    $this->container->set('ban.ip_manager', $this->banManager->reveal());

    // Mock a request.
    $this->request = $this->prophesize(Request::class);

    // Mock the request_stack service, make it return our mocked request,
    // and register it in the container.
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
    $this->container->set('request_stack', $this->requestStack->reveal());

    // Mock the logger.factory service, make it return the Rules logger channel,
    // and register it in the container.
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('rules')->willReturn($this->logger->reveal());
    $this->container->set('logger.factory', $logger_factory->reveal());

    // Instantiate the rules_unban_ip action.
    $this->action = $this->actionManager->createInstance('rules_unban_ip');
  }

  /**
   * Tests the summary.
   *
   * @covers ::summary
   */
  public function testSummary() {
    $this->assertEquals('Remove the ban on an IP address', $this->action->summary());
  }

  /**
   * Tests the action execution with Context IPv4.
   *
   * Uses the 192.0.2.0/24 "TEST-NET" address block as defined in RFC3330.
   *
   * @see http://en.wikipedia.org/wiki/Reserved_IP_addresses
   * @see https://tools.ietf.org/html/rfc3330
   *
   * @covers ::execute
   */
  public function testActionExecutionWithContextIpv4() {
    // TEST-NET-1 IPv4.
    $ipv4 = '192.0.2.0';
    $this->action->setContextValue('ip', $ipv4);

    $this->banManager->unbanIp($ipv4)->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger->notice('Removed ban on IP address %ip', ['%ip' => $ipv4])->shouldHaveBeenCalled();
  }

  /**
   * Tests the action execution with Context IPv6.
   *
   * Uses the 192.0.2.0/24 "TEST-NET" address block as defined in RFC3330.
   *
   * @see http://en.wikipedia.org/wiki/Reserved_IP_addresses
   * @see https://tools.ietf.org/html/rfc3330
   *
   * @covers ::execute
   */
  public function testActionExecutionWithContextIpv6() {
    // TEST-NET-1 IPv4 '192.0.2.0' converted to IPv6.
    $ipv6 = '2002:0:0:0:0:0:c000:200';
    $this->action->setContextValue('ip', $ipv6);

    $this->banManager->unbanIp($ipv6)->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger->notice('Removed ban on IP address %ip', ['%ip' => $ipv6])->shouldHaveBeenCalled();
  }

  /**
   * Tests the action execution without Context IP set.
   *
   * Should fallback to the current IP of the request.
   *
   * @covers ::execute
   */
  public function testActionExecutionWithoutContextIp() {
    // TEST-NET-1 IPv4.
    $ip = '192.0.2.0';

    $this->request->getClientIp()->willReturn($ip)->shouldBeCalledTimes(1);

    $this->banManager->unbanIp($ip)->shouldBeCalledTimes(1);

    $this->action->execute();

    $this->logger->notice('Removed ban on IP address %ip', ['%ip' => $ip])->shouldHaveBeenCalled();
  }

}
