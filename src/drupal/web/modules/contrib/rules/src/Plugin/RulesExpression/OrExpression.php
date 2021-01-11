<?php

namespace Drupal\rules\Plugin\RulesExpression;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ConditionExpressionContainer;

/**
 * Evaluates a group of conditions with a logical OR.
 *
 * @RulesExpression(
 *   id = "rules_or",
 *   label = @Translation("Condition set (OR)")
 * )
 */
class OrExpression extends ConditionExpressionContainer {

  /**
   * {@inheritdoc}
   */
  public function evaluate(ExecutionStateInterface $state) {
    // Use the iterator to ensure the conditions are sorted.
    foreach ($this as $condition) {
      /* @var \Drupal\rules\Engine\ExpressionInterface $condition */
      if ($condition->executeWithState($state)) {
        $this->rulesDebugLogger->info('%label evaluated to %result.', [
          '%label' => $this->getLabel(),
          '%result' => 'TRUE',
        ]);
        return TRUE;
      }
    }
    $this->rulesDebugLogger->info('%label evaluated to %result.', [
      '%label' => $this->getLabel(),
      '%result' => 'FALSE',
    ]);
    // An empty OR should return TRUE. Otherwise, if all conditions evaluate
    // to FALSE we return FALSE.
    return empty($this->conditions);
  }

  /**
   * {@inheritdoc}
   */
  protected function allowsMetadataAssertions() {
    // We cannot guarantee child expressions are executed, thus we cannot allow
    // metadata assertions.
    return FALSE;
  }

}
