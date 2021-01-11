<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Node is promoted' condition.
 *
 * @Condition(
 *   id = "rules_node_is_promoted",
 *   label = @Translation("Node is promoted"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the node for which to evaluate the condition."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 */
class NodeIsPromoted extends RulesConditionBase {

  /**
   * Checks if a node is promoted.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the node is promoted.
   */
  protected function doEvaluate(NodeInterface $node) {
    return $node->isPromoted();
  }

}
