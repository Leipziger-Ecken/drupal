<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Promotes a content item.
 *
 * @RulesAction(
 *   id = "rules_node_promote",
 *   label = @Translation("Promote selected content to front page"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the content item to promote."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class NodePromote extends RulesActionBase {

  /**
   * Executes the action with the given context.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to modify.
   */
  protected function doExecute(NodeInterface $node) {
    $node->setPromoted(NodeInterface::PROMOTED);
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // The node should be auto-saved after the execution.
    return ['node'];
  }

}
