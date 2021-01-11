<?php

namespace Drupal\Tests\rules\Unit\Integration\Condition;

use Drupal\Core\Plugin\Context\Context;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Tests\rules\Unit\Integration\RulesIntegrationTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\Condition\IpIsBanned
 * @group RulesCondition
 */
class IpIsBannedTest extends RulesIntegrationTestBase {

  /**
   * The condition to be tested.
   *
   * @var \Drupal\rules\Core\RulesConditionInterface
   */
  protected $condition;

  /**
   * The ban manager used to ban the IP.
   *
   * @var \Drupal\ban\BanIpManagerInterface
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

    $this->condition = $this->conditionManager->createInstance('rules_ip_is_banned');
  }

  /**
   * Tests evaluating the condition.
   *
   * @covers ::evaluate
   */
  public function testConditionEvaluation() {
    // Test an IPv4 address that has not been banned; should return FALSE.
    // TEST-NET-1 IPv4.
    $ipv4 = '192.0.2.0';

    $this->banManager->isBanned($ipv4)->willReturn(FALSE);
    $context = $this->condition->getContext('ip');
    $context = Context::createFromContext($context, $this->getTypedData('string', $ipv4));
    $this->condition->setContext('ip', $context);
    $this->assertFalse($this->condition->evaluate());

    // Test an IPv6 address that has not been banned; should return FALSE.
    // TEST-NET-1 IPv4 '192.0.2.0' converted to IPv6.
    $ipv6 = '2002:0:0:0:0:0:c000:200';

    $this->banManager->isBanned($ipv6)->willReturn(FALSE);
    $context = $this->condition->getContext('ip');
    $context = Context::createFromContext($context, $this->getTypedData('string', $ipv6));
    $this->condition->setContext('ip', $context);
    $this->assertFalse($this->condition->evaluate());

    // Ban an IPv4 address and an IPv6 address.
    $ip_addresses_to_ban = [
      // TEST-NET-1 IPv4.
      'IPv4' => ['ip' => '192.0.2.0'],
      // TEST-NET-1 IPv4 '192.0.2.0' converted to IPv6.
      'IPv6' => ['ip' => '2002:0:0:0:0:0:c000:200'],
    ];

    // Ban the above IP addresses.
    foreach ($ip_addresses_to_ban as $ip_address_to_ban) {
      $this->banManager->banIp($ip_address_to_ban['ip']);
      $this->banManager->isBanned($ip_address_to_ban['ip'])->willReturn(TRUE);
    }

    // Test an IPv4 address that has been banned; should return TRUE.
    $context = $this->condition->getContext('ip');
    $context = Context::createFromContext($context, $this->getTypedData('string', $ip_addresses_to_ban['IPv4']['ip']));
    $this->condition->setContext('ip', $context);
    $this->assertTrue($this->condition->evaluate());

    // Test an IPv6 address that has been banned; should return TRUE.
    $context = $this->condition->getContext('ip');
    $context = Context::createFromContext($context, $this->getTypedData('string', $ip_addresses_to_ban['IPv6']['ip']));
    $this->condition->setContext('ip', $context);
    $this->assertTrue($this->condition->evaluate());
  }

}
