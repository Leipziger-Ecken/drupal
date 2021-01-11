<?php

namespace Drupal\Tests\rules\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Engine\RulesComponent;

/**
 * Test using the Rules API with the placeholder token replacement system.
 *
 * @group Rules
 */
class TokenIntegrationTest extends RulesKernelTestBase {

  /**
   * Tests that date tokens are formatted correctly.
   */
  public function testSystemDateToken() {
    // Configure a simple rule with one action. and token replacements enabled.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setValue('message', "The date is {{ date | format_date('custom', 'Y-m') }}!")
        ->setValue('type', 'status')
        ->process('message', 'rules_tokens')
        ->setConfigKey('action_id', 'rules_system_message')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule();
    $rule->addExpressionObject($action);
    RulesComponent::create($rule)
      ->addContextDefinition('date', ContextDefinition::create('timestamp'))
      ->setContextValue('date', $this->time->getRequestTime())
      ->execute();

    $messages = $this->messenger->all();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $date = $date_formatter->format($this->time->getRequestTime(), 'custom', 'Y-m');
    $this->assertEquals("The date is $date!", (string) $messages[MessengerInterface::TYPE_STATUS][0]);
  }

  /**
   * Tests that global context variable tokens are replaced correctly.
   */
  public function testGlobalContextVariableTokens() {
    // Configure a simple rule with one action and token replacements enabled.
    $action = $this->expressionManager->createInstance('rules_action',
      ContextConfig::create()
        ->setValue('message', "The date is {{ @rules.current_date_context:current_date | format_date('custom', 'Y-m') }}!")
        ->setValue('type', 'status')
        ->process('message', 'rules_tokens')
        ->setConfigKey('action_id', 'rules_system_message')
        ->toArray()
    );

    $rule = $this->expressionManager->createRule();
    $rule->addExpressionObject($action);
    RulesComponent::create($rule)
      ->execute();

    $messages = $this->messenger->all();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $date = $date_formatter->format($this->time->getRequestTime(), 'custom', 'Y-m');
    $this->assertEquals("The date is $date!", (string) $messages[MessengerInterface::TYPE_STATUS][0]);
  }

}
