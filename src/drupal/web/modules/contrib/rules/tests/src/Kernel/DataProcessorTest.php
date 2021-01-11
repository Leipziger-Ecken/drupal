<?php

namespace Drupal\Tests\rules\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Engine\RulesComponent;

/**
 * Test the data processor plugins during Rules evaluation.
 *
 * @group Rules
 */
class DataProcessorTest extends RulesKernelTestBase {

  /**
   * Tests that the numeric offset plugin works.
   */
  public function testNumericOffset() {
    // Configure a simple rule with one action.
    $action = $this->expressionManager->createInstance('rules_action',
      // @todo Actually the data processor plugin only applies to numbers, so is
      // kind of an invalid configuration. Since the configuration is not
      // validated during execution this works for now.
      ContextConfig::create()
        ->map('message', 'message')
        ->map('type', 'type')
        ->process('message', 'rules_numeric_offset', [
          'offset' => 1,
        ])
        ->setConfigKey('action_id', 'rules_system_message')
        ->toArray()
    );

    $component = RulesComponent::create($this->expressionManager->createRule())
      ->addContextDefinition('message', ContextDefinition::create('string'))
      ->addContextDefinition('type', ContextDefinition::create('string'))
      ->setContextValue('message', 1)
      ->setContextValue('type', 'status');

    $component->getExpression()
      ->addExpressionObject($action);

    $component->execute();

    $messages = $this->messenger->all();
    // The original value was 1 and the processor adds 1, so the result should
    // be 2.
    $this->assertEquals((string) $messages[MessengerInterface::TYPE_STATUS][0], '2');
  }

}
