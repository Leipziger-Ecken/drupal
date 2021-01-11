<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ConditionExpressionInterface;
use Drupal\rules\Plugin\RulesExpression\OrExpression;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesExpression\OrExpression
 * @group Rules
 */
class OrExpressionTest extends RulesUnitTestBase {

  /**
   * The 'or' condition container being tested.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionContainerInterface
   */
  protected $or;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->or = new OrExpression([], '', ['label' => 'Condition set (OR)'], $this->expressionManager->reveal(), $this->rulesDebugLogger->reveal());
  }

  /**
   * Tests one condition.
   */
  public function testOneCondition() {
    // The method on the test condition must be called once.
    $this->trueConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->shouldBeCalledTimes(1);

    $this->or->addExpressionObject($this->trueConditionExpression->reveal());
    $this->assertTrue($this->or->execute(), 'Single condition returns TRUE.');
  }

  /**
   * Tests an empty OR.
   */
  public function testEmptyOr() {
    $property = new \ReflectionProperty($this->or, 'conditions');
    $property->setAccessible(TRUE);

    $this->assertEmpty($property->getValue($this->or));
    $this->assertTrue($this->or->execute(), 'Empty OR returns TRUE.');
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
      ->shouldNotBeCalled();

    $this->or
      ->addExpressionObject($this->trueConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal());

    $this->assertTrue($this->or->execute(), 'Two conditions returns TRUE.');
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

    $second_condition->executeWithState(Argument::type(ExecutionStateInterface::class))
      ->willReturn(FALSE)
      ->shouldBeCalledTimes(1);

    $this->or
      ->addExpressionObject($this->falseConditionExpression->reveal())
      ->addExpressionObject($second_condition->reveal());

    $this->assertFalse($this->or->execute(), 'Two false conditions return FALSE.');
  }

}
