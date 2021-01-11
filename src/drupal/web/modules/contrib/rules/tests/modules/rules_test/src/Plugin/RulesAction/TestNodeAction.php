<?php

namespace Drupal\rules_test\Plugin\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a test action that sets a node title.
 *
 * @RulesAction(
 *   id = "rules_test_node",
 *   label = @Translation("Test node title action"),
 *   category = @Translation("Tests"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node to set the title on")
 *     ),
 *     "title" = @ContextDefinition("string",
 *       label = @Translation("New title that should be set")
 *     ),
 *   }
 * )
 */
class TestNodeAction extends RulesActionBase {

  /**
   * Sets the node title.
   *
   * @param \Drupa\node\NodeInterface $node
   *   The node.
   * @param string $title
   *   The title.
   */
  protected function doExecute(NodeInterface $node, $title) {
    $node->setTitle($title);
  }

  /**
   * {@inheritdoc}
   */
  public function autoSaveContext() {
    // The node where we changed the title should be auto-saved after the
    // execution.
    return ['node'];
  }

}
