<?php

namespace Drupal\rules\Plugin\RulesExpression;

use Drupal\rules\Context\ExecutionStateInterface;
use Drupal\rules\Engine\ActionExpressionContainer;

/**
 * Holds a set of actions and executes all of them.
 *
 * @RulesExpression(
 *   id = "rules_action_set",
 *   label = @Translation("Action set"),
 *   form_class = "\Drupal\rules\Form\Expression\ActionContainerForm"
 * )
 */
class ActionSetExpression extends ActionExpressionContainer {

  /**
   * {@inheritdoc}
   */
  protected function allowsMetadataAssertions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeWithState(ExecutionStateInterface $state) {
    // Use the iterator to ensure the actions are sorted.
    foreach ($this as $action) {
      /* @var \Drupal\rules\Engine\ExpressionInterface $action */
      $action->executeWithState($state);
    }
  }

}
