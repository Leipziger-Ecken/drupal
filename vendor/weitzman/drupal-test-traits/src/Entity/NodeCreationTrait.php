<?php

namespace weitzman\DrupalTestTraits\Entity;

use Drupal\Tests\node\Traits\NodeCreationTrait as CoreNodeCreationTrait;

/**
 * Wraps the node creation trait to track entities for deletion.
 */
trait NodeCreationTrait
{

    use CoreNodeCreationTrait {
        createNode as coreCreateNode;
    }

    /**
     * Creates a node and marks it for automatic cleanup.
     *
     * @param array $settings
     * @return \Drupal\node\NodeInterface
     */
    protected function createNode(array $settings = [])
    {
        $entity = $this->coreCreateNode($settings);
        $this->markEntityForCleanup($entity);
        return $entity;
    }
}
