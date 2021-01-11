<?php

namespace Drupal\Tests\rules\Kernel;

use Drupal\rules\Context\ContextConfig;

/**
 * Tests that action specific config schema works.
 *
 * @group Rules
 */
class ConfigSchemaTest extends RulesKernelTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('rules_component');
  }

  /**
   * Make sure the system send email config schema works on saving.
   *
   * @doesNotPerformAssertions
   */
  public function testMailActionContextSchema() {
    $rule = $this->expressionManager
      ->createRule();
    $rule->addAction('rules_send_email', ContextConfig::create()
      ->setValue('to', ['test@example.com'])
      ->setValue('message', 'mail body')
      ->setValue('subject', 'test subject')
    );

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ])->setExpression($rule);
    $config_entity->save();
  }

}
