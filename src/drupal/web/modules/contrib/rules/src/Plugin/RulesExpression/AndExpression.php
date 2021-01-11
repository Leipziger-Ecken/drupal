<?php

namespace Drupal\rules\Plugin\RulesExpression;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ConditionExpressionContainer;

/**
 * Evaluates a group of conditions with a logical AND.
 *
 * @RulesExpression(
 *   id = "rules_and",
 *   label = @Translation("Condition set (AND)"),
 *   form_class = "\Drupal\rules\Form\Expression\ConditionContainerForm"
 * )
 */
class AndExpression extends ConditionExpressionContainer {

  /**
   * Returns whether there is a configured condition.
   *
   * @todo Remove this once we added the API to access configured conditions.
   *
   * @return bool
   *   TRUE if there are no conditions, FALSE otherwise.
   */
  public function isEmpty() {
    return empty($this->conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(ExecutionStateInterface $state) {
    // Use the iterator to ensure the conditions are sorted.
    foreach ($this as $condition) {
      /* @var \Drupal\rules\Engine\ExpressionInterface $condition */
      if (!$condition->executeWithState($state)) {
        $this->rulesDebugLogger->info('%label evaluated to %result.', [
          '%label' => $this->getLabel(),
          '%result' => 'FALSE',
        ]);
        return FALSE;
      }
    }
    $this->rulesDebugLogger->info('%label evaluated to %result.', [
      '%label' => $this->getLabel(),
      '%result' => 'TRUE',
    ]);
    // An empty AND should return FALSE. Otherwise, if all conditions evaluate
    // to TRUE we return TRUE.
    return !empty($this->conditions);
  }

  /**
   * {@inheritdoc}
   */
  protected function allowsMetadataAssertions() {
    // If the AND is not negated, all child-expressions must be executed - thus
    // assertions can be added it.
    return !$this->isNegated();
  }

}
