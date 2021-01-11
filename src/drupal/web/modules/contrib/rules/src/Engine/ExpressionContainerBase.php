<?php

namespace Drupal\rules\Engine;

use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ExecutionMetadataStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common base class for action and condition expression containers.
 */
abstract class ExpressionContainerBase extends ExpressionBase implements ExpressionContainerInterface {

  /**
   * The expression manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManagerInterface
   */
  protected $expressionManager;

  /**
   * The rules debug logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $rulesDebugLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.rules_expression'),
      $container->get('logger.channel.rules_debug')
    );
  }

  /**
   * Sorts an array of expressions by 'weight' property.
   *
   * Callback for uasort().
   *
   * @param \Drupal\rules\Engine\ExpressionInterface $a
   *   First item for comparison.
   * @param \Drupal\rules\Engine\ExpressionInterface $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sortByWeightProperty(ExpressionInterface $a, ExpressionInterface $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Sorts an array of values using divide, sort and merge process.
   *
   * This is an alternative sort for use when running PHP5, to retain the
   * existing order of items when the weights all default to zero.
   *
   * @param array $array
   *   The array to sort.
   * @param mixed $cmp_function
   *   A comparison function to determine the relative position of two values.
   *
   * @see https://www.drupal.org/project/rules/issues/3101013
   * @todo Remove this function when PHP5 is no longer supported.
   */
  public static function mergesort(array &$array, $cmp_function = 'strcmp') {
    // Arrays of size < 2 require no action.
    if (count($array) < 2) {
      return $array;
    }
    // Split the array in half.
    $halfway = count($array) / 2;
    $array1 = array_slice($array, 0, $halfway);
    $array2 = array_slice($array, $halfway);
    // Recurse to sort the two halves.
    self::mergesort($array1, $cmp_function);
    self::mergesort($array2, $cmp_function);
    // If all of $array1 is <= all of $array2, just append them.
    if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
      $array = array_merge($array1, $array2);
      return;
    }
    // Merge the two sorted arrays into a single sorted array.
    $array = [];
    $ptr1 = $ptr2 = 0;
    while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
      if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
        $array[] = $array1[$ptr1++];
      }
      else {
        $array[] = $array2[$ptr2++];
      }
    }
    // Merge the remainder.
    while ($ptr1 < count($array1)) {
      $array[] = $array1[$ptr1++];
    }
    while ($ptr2 < count($array2)) {
      $array[] = $array2[$ptr2++];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addExpression($plugin_id, ContextConfig $config = NULL) {
    return $this->addExpressionObject(
      $this->expressionManager->createInstance($plugin_id, $config ? $config->toArray() : [])
    );
  }

  /**
   * Determines whether child-expressions are allowed to assert metadata.
   *
   * @return bool
   *   Whether child-expressions are allowed to assert metadata.
   *
   * @see \Drupal\rules\Engine\ExpressionInterface::prepareExecutionMetadataState()
   */
  abstract protected function allowsMetadataAssertions();

  /**
   * {@inheritdoc}
   */
  public function checkIntegrity(ExecutionMetadataStateInterface $metadata_state, $apply_assertions = TRUE) {
    $violation_list = new IntegrityViolationList();
    $this->prepareExecutionMetadataStateBeforeTraversal($metadata_state);
    $apply_assertions = $apply_assertions && $this->allowsMetadataAssertions();
    foreach ($this as $child_expression) {
      $child_violations = $child_expression->checkIntegrity($metadata_state, $apply_assertions);
      $violation_list->addAll($child_violations);
    }
    $this->prepareExecutionMetadataStateAfterTraversal($metadata_state);
    return $violation_list;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareExecutionMetadataState(ExecutionMetadataStateInterface $metadata_state, ExpressionInterface $until = NULL, $apply_assertions = TRUE) {
    if ($until && $this->getUuid() === $until->getUuid()) {
      return TRUE;
    }
    $this->prepareExecutionMetadataStateBeforeTraversal($metadata_state);
    $apply_assertions = $apply_assertions && $this->allowsMetadataAssertions();
    foreach ($this as $child_expression) {
      $found = $child_expression->prepareExecutionMetadataState($metadata_state, $until, $apply_assertions);
      // If the expression was found, we need to stop.
      if ($found) {
        return TRUE;
      }
    }
    $this->prepareExecutionMetadataStateAfterTraversal($metadata_state);
  }

  /**
   * Prepares execution metadata state before traversing through children.
   *
   * @see ::prepareExecutionMetadataState()
   * @see ::checkIntegrity()
   */
  protected function prepareExecutionMetadataStateBeforeTraversal(ExecutionMetadataStateInterface $metadata_state) {
    // Any pre-traversal preparations need to be added here.
  }

  /**
   * Prepares execution metadata state after traversing through children.
   *
   * @see ::prepareExecutionMetadataState()
   * @see ::checkIntegrity()
   */
  protected function prepareExecutionMetadataStateAfterTraversal(ExecutionMetadataStateInterface $metadata_state) {
    // Any post-traversal preparations need to be added here.
  }

}
