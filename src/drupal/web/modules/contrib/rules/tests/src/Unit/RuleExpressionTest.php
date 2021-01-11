<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ConditionExpressionInterface;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Drupal\rules\Plugin\RulesExpression\ActionSetExpression;
use Drupal\rules\Plugin\RulesExpression\RuleExpression;
use Drupal\rules\Plugin\RulesExpression\ActionExpression;
use Drupal\rules\Plugin\RulesExpression\AndExpression;
use Drupal\rules\Plugin\RulesExpression\OrExpression;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesExpression\RuleExpression
 * @group Rules
 */
class RuleExpressionTest extends RulesUnitTestBase {

  /**
   * The rules expression plugin manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $expressionManager;

  /**
   * The rule being tested.
   *
   * @var \Drupal\rules\Engine\RuleExpressionInterface
   */
  protected $rule;

  /**
   * The primary condition container of the rule.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionContainerInterface
   */
  protected $conditions;

  /**
   * The primary action container of the rule.
   *
   * @var \Drupal\rules\Engine\ActionExpressionContainerInterface
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->expressionManager = $this->prophesize(ExpressionManagerInterface::class);

    $this->conditions = new AndExpression([], 'rules_and', ['label' => 'Condition set (AND)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $this->expressionManager->createInstance('rules_and', [])->willReturn($this->conditions);

    $this->actions = new ActionSetExpression([], 'rules_action_set', [], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $this->expressionManager->createInstance('rules_action_set', [])->willReturn($this->actions);

    $this->rule = new RuleExpression([], 'rules_rule', ['label' => 'Rule'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
  }

  /**
   * Tests that a rule is constructed with condition and action containers.
   *
   * @covers ::__construct
   */
  public function testContainersOnConstruct() {
    $this->assertSame($this->conditions, $this->rule->getConditions());
    $this->assertSame($this->actions, $this->rule->getActions());
  }

  /**
   * Tests the condition container setter and getter.
   *
   * @covers ::setConditions
   * @covers ::getConditions
   */
  public function testSetConditionsGetConditions() {
    $or = new OrExpression([], 'rules_or', ['label' => 'Condition set (OR)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $this->rule->setConditions($or);
    $this->assertSame($or, $this->rule->getConditions());

    $and = new AndExpression([], 'rules_and', ['label' => 'Condition set (AND)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $this->rule->setConditions($and);
    $this->assertSame($and, $this->rule->getConditions());
  }

  /**
   * Tests the condition container setter and getter.
   *
   * @covers ::setActions
   * @covers ::getActions
   */
  public function testSetActionsGetActions() {
    $action_set = new ActionSetExpression([], '', [], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $this->rule->setActions($action_set);
    $this->assertSame($action_set, $this->rule->getActions());
  }

  /**
   * Tests that an action fires if a condition passes.
   *
   * @covers ::execute
   */
  public function testActionExecution() {
    // The method on the test action must be called once.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $this->rule
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($this->testActionExpression->reveal())
      ->execute();
  }

  /**
   * Tests that an action does not fire if a condition fails.
   *
   * @covers ::execute
   */
  public function testConditionFails() {
    // The execute method on the action must never be called.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldNotBeCalled();

    $this->rule
      ->addExpressionObject($this->falseConditionExpression->reveal())
      ->addExpressionObject($this->testActionExpression->reveal())
      ->execute();
  }

  /**
   * Tests that an action fires if a condition passes.
   *
   * @covers ::execute
   */
  public function testTwoConditionsTrue() {
    // The method on the test action must be called once.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $this->trueConditionExpression->getWeight()->willReturn(0);

    $second_condition = $this->prophesize(ConditionExpressionInterface::class);
    $second_condition->getUuid()->willReturn('true_uuid2');
    $second_condition->getWeight()->willReturn(0);

    $second_condition->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->willReturn(TRUE);

    $this->rule
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal())
      ->addExpressionObject($this->testActionExpression->reveal())
      ->execute();
  }

  /**
   * Tests that an action does not fire if a condition fails.
   *
   * @covers ::execute
   */
  public function testTwoConditionsFalse() {
    // The execute method on the action must never be called.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldNotBeCalled();
    $this->testActionExpression->getWeight()->willReturn(0);

    $this->trueConditionExpression->getWeight()->willReturn(0);
    $this->falseConditionExpression->getWeight()->willReturn(0);

    $this->rule
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($this->falseConditionExpression->reveal())
      ->addExpressionObject($this->testActionExpression->reveal())
      ->execute();
  }

  /**
   * Tests that nested rules are properly executed.
   *
   * @covers ::execute
   */
  public function testNestedRules() {
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $nested = new RuleExpression([], 'rules_rule', ['label' => 'Rule'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    // We need to replace the action and condition container to not have the
    // same instances as in the outer rule.
    $nested->setConditions(new AndExpression([], 'rules_and', ['label' => 'Condition set (AND)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal()));
    $nested->setActions(new ActionSetExpression([], 'rules_action_set', [], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal()));

    $nested->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($this->testActionExpression->reveal());

    $this->rule
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($nested)
      ->execute();
  }

  /**
   * Tests that a nested expression can be retrieved by UUID.
   */
  public function testLookupExpression() {
    // Test Conditions.
    $this->rule->addExpressionObject($this->trueConditionExpression->reveal());
    $uuid = $this->trueConditionExpression->reveal()->getUuid();
    $this->assertSame($this->trueConditionExpression->reveal(), $this->rule->getExpression($uuid));

    // Test actions.
    $this->rule->addExpressionObject($this->testActionExpression->reveal());
    $uuid = $this->testActionExpression->reveal()->getUuid();
    $this->assertSame($this->testActionExpression->reveal(), $this->rule->getExpression($uuid));

    $this->assertFalse($this->rule->getExpression('invalid UUID'));
  }

  /**
   * Tests that removing expressions by indices works.
   */
  public function testDeletingExpressions() {
    // Create a rule with 2 conditions and 2 actions.
    $this->rule->addExpressionObject($this->trueConditionExpression->reveal());
    $this->rule->addExpressionObject($this->falseConditionExpression->reveal());
    $this->rule->addExpressionObject($this->testActionExpression->reveal());
    $second_action = $this->prophesize(ActionExpression::class);
    $second_action->getUuid()->willReturn('action_uuid2');
    $this->rule->addExpressionObject($second_action->reveal());

    $this->trueConditionExpression->getWeight()->willReturn(0);
    $this->falseConditionExpression->getWeight()->willReturn(0);
    $this->testActionExpression->getWeight()->willReturn(0);
    $second_action->getWeight()->willReturn(0);

    // Delete the first action.
    $uuid = $this->testActionExpression->reveal()->getUuid();
    $this->rule->deleteExpression($uuid);
    $this->assertEquals(2, count($this->rule->getConditions()->getIterator()));
    $this->assertEquals(1, count($this->rule->getActions()->getIterator()));

    // Delete the second condition.
    $uuid = $this->falseConditionExpression->reveal()->getUuid();
    $this->rule->deleteExpression($uuid);
    $this->assertEquals(1, count($this->rule->getConditions()->getIterator()));
    $this->assertEquals(1, count($this->rule->getActions()->getIterator()));

    // Delete the remaining action.
    $uuid = $second_action->reveal()->getUuid();
    $this->rule->deleteExpression($uuid);
    $this->assertEquals(1, count($this->rule->getConditions()->getIterator()));
    $this->assertEquals(0, count($this->rule->getActions()->getIterator()));

    // Delete the remaining condition, rule should be empty now.
    $uuid = $this->trueConditionExpression->reveal()->getUuid();
    $this->rule->deleteExpression($uuid);
    $this->assertEquals(0, count($this->rule->getConditions()->getIterator()));
    $this->assertEquals(0, count($this->rule->getActions()->getIterator()));
  }

}
