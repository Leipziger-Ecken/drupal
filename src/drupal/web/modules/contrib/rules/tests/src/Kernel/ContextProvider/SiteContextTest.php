<?php

namespace Drupal\Tests\rules\Kernel\ContextProvider;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\user\ContextProvider\CurrentUserContext
 *
 * @group rules
 */
class SiteContextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rules',
    'typed_data',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts() {
    $context_repository = $this->container->get('context.repository');

    // Test an authenticated account.
    $authenticated = User::create([
      'name' => $this->randomMachineName(),
    ]);
    $authenticated->save();
    $authenticated = User::load($authenticated->id());
    $this->container->get('current_user')->setAccount($authenticated);

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@rules.site_context:site', $contexts);
    $this->assertSame('site', $contexts['@rules.site_context:site']->getContextDefinition()->getDataType());
    $this->assertTrue($contexts['@rules.site_context:site']->hasContextValue());
    $this->assertNotNull($contexts['@rules.site_context:site']->getContextValue());

    // Test an anonymous account.
    $anonymous = $this->prophesize(AccountInterface::class);
    $anonymous->id()->willReturn(0);
    $this->container->get('current_user')->setAccount($anonymous->reveal());

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@rules.site_context:site', $contexts);
    $this->assertSame('site', $contexts['@rules.site_context:site']->getContextDefinition()->getDataType());
    $this->assertTrue($contexts['@rules.site_context:site']->hasContextValue());
  }

}
