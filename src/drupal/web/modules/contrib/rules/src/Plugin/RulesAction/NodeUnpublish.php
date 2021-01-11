<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Unpublishes a content item.
 *
 * @RulesAction(
 *   id = "rules_node_unpublish",
 *   label = @Translation("Unpublish a content item"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the content item to unpublish."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class NodeUnpublish extends RulesActionBase {

  /**
   * Unpublishes the Node.
   *
   * @param \Drupal\Core\Entity\NodeInterface $node
   *   The node to modify.
   */
  protected function doExecute(NodeInterface $node) {
    $node->setUnpublished();
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // The node should be auto-saved after the execution.
    return ['node'];
  }

}
