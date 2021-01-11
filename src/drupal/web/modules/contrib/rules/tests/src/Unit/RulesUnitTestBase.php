<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ActionExpressionInterface;
use Drupal\rules\Engine\ConditionExpressionInterface;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Helper class with mock objects.
 */
abstract class RulesUnitTestBase extends UnitTestCase {

  /**
   * A mocked condition that always evaluates to TRUE.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $trueConditionExpression;

  /**
   * A mocked condition that always evaluates to FALSE.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $falseConditionExpression;

  /**
   * A mocked dummy action object.
   *
   * @var \Drupal\rules\Engine\ActionExpressionInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $testActionExpression;

  /**
   * A mocked dummy action object.
   *
   * @var \Drupal\rules\Engine\ActionExpressionInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $testFirstActionExpression;

  /**
   * The mocked expression manager object.
   *
   * @var \Drupal\rules\Engine\ExpressionPluginManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $expressionManager;

  /**
   * The mocked expression manager object.
   *
   * @var \Drupal\rules\src\Logger\|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $rulesDebugLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // A Condition that's always TRUE.
    $this->trueConditionExpression = $this->prophesize(ConditionExpressionInterface::class);
    $this->trueConditionExpression->getUuid()->willReturn('true_uuid1');
    $this->trueConditionExpression->getWeight()->willReturn(0);

    $this->trueConditionExpression->execute()->willReturn(TRUE);
    $this->trueConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->willReturn(TRUE);

    // A Condition that's always FALSE.
    $this->falseConditionExpression = $this->prophesize(ConditionExpressionInterface::class);
    $this->falseConditionExpression->getUuid()->willReturn('false_uuid1');
    $this->falseConditionExpression->getWeight()->willReturn(0);

    $this->falseConditionExpression->execute()->willReturn(FALSE);
    $this->falseConditionExpression->executeWithState(
      Argument::type(ExecutionStateInterface::class))->willReturn(FALSE);

    // An Action with a low weight.
    $this->testFirstActionExpression = $this->prophesize(ActionExpressionInterface::class);
    $this->testFirstActionExpression->getUuid()->willReturn('action_uuid0');
    $this->testFirstActionExpression->getWeight()->willReturn(-1);

    // An Action with a heavier weight.
    $this->testActionExpression = $this->prophesize(ActionExpressionInterface::class);
    $this->testActionExpression->getUuid()->willReturn('action_uuid1');
    $this->testActionExpression->getWeight()->willReturn(0);

    $this->expressionManager = $this->prophesize(ExpressionManagerInterface::class);
    $this->rulesDebugLogger = $this->prophesize(LoggerChannelInterface::class);
  }

}
