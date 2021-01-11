<?php

namespace Drupal\rules\Engine;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\rules\Context\ContextConfig;

/**
 * Defines an interface for the expression plugin manager.
 */
interface ExpressionManagerInterface extends PluginManagerInterface {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\rules\Engine\ExpressionInterface
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []);

  /**
   * Creates a new rule.
   *
   * @param \Drupal\rules\Context\ContextConfig $configuration
   *   (optional) The context configuration used to create the plugin instance.
   *
   * @return \Drupal\rules\Engine\RuleExpressionInterface
   *   The created rule.
   */
  public function createRule(ContextConfig $configuration = NULL);

  /**
   * Creates a new action set.
   *
   * @param \Drupal\rules\Context\ContextConfig $configuration
   *   (optional) The context configuration used to create the plugin instance.
   *
   * @return \Drupal\rules\Plugin\RulesExpression\ActionSetExpression
   *   The created action set.
   */
  public function createActionSet(ContextConfig $configuration = NULL);

  /**
   * Creates a new action expression.
   *
   * @param string $id
   *   The action plugin id.
   * @param \Drupal\rules\Context\ContextConfig $configuration
   *   (optional) The context configuration used to create the plugin instance.
   *
   * @return \Drupal\rules\Engine\ActionExpressionInterface
   *   The created action expression.
   */
  public function createAction($id, ContextConfig $configuration = NULL);

  /**
   * Creates a new condition expression.
   *
   * @param string $id
   *   The condition plugin id.
   * @param \Drupal\rules\Context\ContextConfig $configuration
   *   (optional) The context configuration used to create the plugin instance.
   *
   * @return \Drupal\rules\Engine\ConditionExpressionInterface
   *   The created condition expression.
   */
  public function createCondition($id, ContextConfig $configuration = NULL);

  /**
   * Creates a new 'and' condition container.
   *
   * @return \Drupal\rules\Engine\ConditionExpressionContainerInterface
   *   The created 'and' condition container.
   */
  public function createAnd();

  /**
   * Creates a new 'or' condition container.
   *
   * @return \Drupal\rules\Engine\ConditionExpressionContainerInterface
   *   The created 'or' condition container.
   */
  public function createOr();

}
