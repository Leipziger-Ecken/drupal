<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ActionExpressionInterface;
use Drupal\rules\Plugin\RulesExpression\ActionSetExpression;
use Drupal\rules\Plugin\RulesExpression\ActionExpression;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesExpression\ActionSetExpression
 * @group Rules
 */
class ActionSetExpressionTest extends RulesUnitTestBase {

  /**
   * The action set being tested.
   *
   * @var \Drupal\rules\Plugin\RulesExpression\ActionSetExpression
   */
  protected $actionSet;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // TestActionSetExpression is defined below.
    $this->actionSet = new TestActionSetExpression([], '', [], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
  }

  /**
   * Tests that an action in the set fires.
   */
  public function testActionExecution() {
    // The execute method on the test action must be called once.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $this->actionSet->addExpressionObject($this->testActionExpression->reveal())->execute();
  }

  /**
   * Tests that two actions in the set fire both.
   */
  public function testTwoActionExecution() {
    // The execute method on the test action must be called once.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    // The execute method on the second action must be called once.
    $second_action = $this->prophesize(ActionExpressionInterface::class);
    $second_action->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->shouldBeCalledTimes(1);
    $second_action->getUuid()->willReturn('uuid2');
    $second_action->getWeight()->willReturn(0);

    $this->actionSet->addExpressionObject($this->testActionExpression->reveal())
      ->addExpressionObject($second_action->reveal())
      ->execute();
  }

  /**
   * Tests that nested action sets work.
   */
  public function testNestedActionExecution() {
    // The execute method on the test action must be called twice.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(2);

    $inner = new ActionSetExpression([], '', [], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
    $inner->addExpressionObject($this->testActionExpression->reveal());

    $this->actionSet->addExpressionObject($this->testActionExpression->reveal())
      ->addExpressionObject($inner)
      ->execute();
  }

  /**
   * Tests that a nested action can be retrieved by UUID.
   */
  public function testLookupAction() {
    $this->actionSet->addExpressionObject($this->testActionExpression->reveal());
    $uuid = $this->testActionExpression->reveal()->getUuid();
    $lookup_action = $this->actionSet->getExpression($uuid);
    $this->assertSame($this->testActionExpression->reveal(), $lookup_action);
    $this->assertFalse($this->actionSet->getExpression('invalid UUID'));
  }

  /**
   * Tests deleting an action from the container.
   */
  public function testDeletingAction() {
    $this->actionSet->addExpressionObject($this->testActionExpression->reveal());
    $second_action = $this->prophesize(ActionExpression::class);
    $this->actionSet->addExpressionObject($second_action->reveal());

    // Get the UUID of the first action added.
    $uuid = $this->testActionExpression->reveal()->getUuid();
    $this->assertTrue($this->actionSet->deleteExpression($uuid));

    // Now only the second action remains.
    foreach ($this->actionSet as $action) {
      $this->assertSame($second_action->reveal(), $action);
    }
  }

  /**
   * Tests evaluation order with two actions.
   */
  public function testEvaluationOrder() {
    // The execute method on the second action must be called once.
    $this->testActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    // The execute method on the test action must be called once.
    $this->testFirstActionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    // The 'first' action should be called first, because of weight,
    // even though it is added second.
    $this->actionSet
      ->addExpressionObject($this->testActionExpression->reveal())
      ->addExpressionObject($this->testFirstActionExpression->reveal());

    // The $result variable is a test-only variable to hold the return value
    // of test actions, which normally don't return a value. We do this so we
    // can verify the order of execution.
    $this->assertEquals(['action_uuid0', 'action_uuid1'], $this->actionSet->execute());
  }

}

/**
 * A wrapper around ActionSetExpression.
 *
 * This class is needed because actions don't return anything when executed,
 * so there is normally no way to test execution order of actions.
 * This strategy is fragile because this test class MUST replicate the
 * executeWithState() method of the parent class exactly as well as return
 * the array of UUIDs.
 */
class TestActionSetExpression extends ActionSetExpression {

  /**
   * {@inheritdoc}
   */
  public function executeWithState(ExecutionStateInterface $state) {
    $uuids = [];
    // Use the iterator to ensure the actions are sorted.
    foreach ($this as $action) {
      $action->executeWithState($state);
      $uuids[] = $action->getUuid();
    }
    // Return array of UUID in same order as the actions were executed.
    return $uuids;
  }

}
