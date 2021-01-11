<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Makes a content item not sticky.
 *
 * @RulesAction(
 *   id = "rules_node_make_unsticky",
 *   label = @Translation("Make selected content not sticky"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the content item to make not sticky."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class NodeMakeUnsticky extends RulesActionBase {

  /**
   * Executes the action with the given context.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to modify.
   */
  protected function doExecute(NodeInterface $node) {
    $node->setSticky(NodeInterface::NOT_STICKY);
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // The node should be auto-saved after the execution.
    return ['node'];
  }

}
