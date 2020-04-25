<?php

namespace weitzman\DrupalTestTraits\Tests\Entity;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test the node creation trait.
 */
class NodeCreationTraitTest extends ExistingSiteBase
{
    public function testAutoCleanup()
    {
        $node = $this->createNode(['type' => 'article']);
        $this->assertCount(1, $this->cleanupEntities);
        $this->assertEquals($node->id(), $this->cleanupEntities[0]->id());
    }
}
