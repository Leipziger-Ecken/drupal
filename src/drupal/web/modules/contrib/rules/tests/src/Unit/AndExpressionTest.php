<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ConditionExpressionInterface;
use Drupal\rules\Plugin\RulesExpression\AndExpression;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesExpression\AndExpression
 * @group Rules
 */
class AndExpressionTest extends RulesUnitTestBase {

  /**
   * The 'and' condition container being tested.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionContainerInterface
   */
  protected $and;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->and = new AndExpression([], '', ['label' => 'Condition set (AND)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
  }

  /**
   * Tests one condition.
   */
  public function testOneCondition() {
    // The method on the test condition must be called once.
    $this->trueConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $this->and->addExpressionObject($this->trueConditionExpression->reveal());
    $this->assertTrue($this->and->execute(), 'Single condition returns TRUE.');
  }

  /**
   * Tests an empty AND.
   */
  public function testEmptyAnd() {
    $property = new \ReflectionProperty($this->and, 'conditions');
    $property->setAccessible(TRUE);

    $this->assertEmpty($property->getValue($this->and));
    $this->assertFalse($this->and->execute(), 'Empty AND returns FALSE.');
  }

  /**
   * Tests two true conditions.
   */
  public function testTwoConditions() {
    // The method on the test condition must be called once.
    $this->trueConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $second_condition = $this->prophesize(ConditionExpressionInterface::class);
    $second_condition->getUuid()->willReturn('true_uuid2');
    $second_condition->getWeight()->willReturn(0);

    $second_condition->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);

    $this->and
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal());

    $this->assertTrue($this->and->execute(), 'Two conditions returns TRUE.');
  }

  /**
   * Tests two false conditions.
   */
  public function testTwoFalseConditions() {
    // The method on the test condition must be called once.
    $this->falseConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $second_condition = $this->prophesize(ConditionExpressionInterface::class);
    $second_condition->getUuid()->willReturn('false_uuid2');
    $second_condition->getWeight()->willReturn(0);

    // Evaluation of an AND condition group should stop with first FALSE.
    // The second condition should not be evaluated.
    $second_condition->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->willReturn(FALSE)
      ->shouldNotBeCalled();

    $this->and
      ->addExpressionObject($this->falseConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal());

    $this->assertFalse($this->and->execute(), 'Two false conditions return FALSE.');
  }

  /**
   * Tests evaluation order with two conditions.
   */
  public function testEvaluationOrder() {
    // The method on the false test condition must be called once.
    $this->falseConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);
    // Set weight to 1 so it will be evaluated second.
    $this->falseConditionExpression->getWeight()->willReturn(1);

    $second_condition = $this->prophesize(ConditionExpressionInterface::class);
    $second_condition->getUuid()->willReturn('true_uuid2');
    $second_condition->getWeight()->willReturn(0);

    // If the above false condition is evaluated first, the second condition
    // will not be called. If the evaluation order is correct, then it should
    // be called exactly once.
    $second_condition->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(1);

    // Second condition should be called first, because of weight.
    $this->and
      ->addExpressionObject($this->falseConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal());

    $this->assertFalse($this->and->execute(), 'Correct execution order of conditions.');
  }

}
