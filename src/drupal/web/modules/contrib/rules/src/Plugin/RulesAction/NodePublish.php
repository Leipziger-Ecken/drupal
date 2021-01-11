<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Publishes a content item.
 *
 * @RulesAction(
 *   id = "rules_node_publish",
 *   label = @Translation("Publish a content item"),
 *   category = @Translation("Content"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node"),
 *       description = @Translation("Specifies the content item to publish."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class NodePublish extends RulesActionBase {

  /**
   * Publishes the content.
   *
   * @param \Drupal\Core\Entity\NodeInterface $node
   *   The node to modify.
   */
  protected function doExecute(NodeInterface $node) {
    $node->setPublished();
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // The node should be auto-saved after the execution.
    return ['node'];
  }

}
