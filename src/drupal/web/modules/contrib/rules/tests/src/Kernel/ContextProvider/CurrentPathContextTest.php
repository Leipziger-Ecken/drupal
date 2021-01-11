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
class CurrentPathContextTest extends KernelTestBase {

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
    $this->assertArrayHasKey('@rules.current_path_context:current_path', $contexts);
    $this->assertSame('current_path', $contexts['@rules.current_path_context:current_path']->getContextDefinition()->getDataType());
    $this->assertTrue($contexts['@rules.current_path_context:current_path']->hasContextValue());
    $this->assertNotNull($contexts['@rules.current_path_context:current_path']->getContextValue());

    // Test an anonymous account.
    $anonymous = $this->prophesize(AccountInterface::class);
    $anonymous->id()->willReturn(0);
    $this->container->get('current_user')->setAccount($anonymous->reveal());

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@rules.current_path_context:current_path', $contexts);
    $this->assertSame('current_path', $contexts['@rules.current_path_context:current_path']->getContextDefinition()->getDataType());
    $this->assertTrue($contexts['@rules.current_path_context:current_path']->hasContextValue());
  }

}
