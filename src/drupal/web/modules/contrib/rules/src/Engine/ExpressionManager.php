<?php

namespace Drupal\rules\Engine;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\rules\Annotation\RulesExpression;
use Drupal\rules\Context\ContextConfig;

/**
 * Plugin manager for all Rules expressions.
 *
 * @see \Drupal\rules\Engine\ExpressionInterface
 */
class ExpressionManager extends DefaultPluginManager implements ExpressionManagerInterface {

  /**
   * The UUID generating service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructor.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, UuidInterface $uuid_service, $plugin_definition_annotation_name = RulesExpression::class) {
    $this->alterInfo('rules_expression');
    parent::__construct('Plugin/RulesExpression', $namespaces, $module_handler, ExpressionInterface::class, $plugin_definition_annotation_name);
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);

    // Make sure that the instance has a UUID and generate one if necessary.
    if (!$instance->getUuid()) {
      $instance->setUuid($this->uuidService->generate());
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function createRule(ContextConfig $configuration = NULL) {
    $config_array = is_null($configuration) ? [] : $configuration->toArray();
    return $this->createInstance('rules_rule', $config_array);
  }

  /**
   * {@inheritdoc}
   */
  public function createActionSet(ContextConfig $configuration = NULL) {
    $config_array = is_null($configuration) ? [] : $configuration->toArray();
    return $this->createInstance('rules_action_set', $config_array);
  }

  /**
   * {@inheritdoc}
   */
  public function createAction($id, ContextConfig $configuration = NULL) {
    $config_array = is_null($configuration) ? [] : $configuration->toArray();
    return $this->createInstance('rules_action', [
      'action_id' => $id,
    ] + $config_array);
  }

  /**
   * {@inheritdoc}
   */
  public function createCondition($id, ContextConfig $configuration = NULL) {
    $config_array = is_null($configuration) ? [] : $configuration->toArray();
    return $this->createInstance('rules_condition', [
      'condition_id' => $id,
    ] + $config_array);
  }

  /**
   * {@inheritdoc}
   */
  public function createAnd() {
    return $this->createInstance('rules_and');
  }

  /**
   * {@inheritdoc}
   */
  public function createOr() {
    return $this->createInstance('rules_or');
  }

}
