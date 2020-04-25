<?php

namespace weitzman\DrupalTestTraits\Tests\Entity;

use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test the media creation trait.
 */
class MediaCreationTraitTest extends ExistingSiteBase
{
    use MediaCreationTrait;
    protected function setUp()
    {
        parent::setUp();
        $status = \Drupal::service('module_installer')
            ->install(['media']);
        $this->assertTrue($status);
    }

    public function testMediaAutoCleanup()
    {
        $mediaType = $this->createMediaType('image', [
          'id' => 'test_media',
          'label' => 'Test',
        ]);
        $media = $this->createMedia([
          'bundle' => $mediaType->id(),
        ]);
        $this->assertCount(2, $this->cleanupEntities);
        $this->assertEquals($mediaType->id(), $this->cleanupEntities[0]->id());
        $this->assertEquals($media->id(), $this->cleanupEntities[1]->id());
    }

    public function tearDown()
    {
        // Delete all the media content before uninstalling the module.
        $this->cleanupEntities[0]->delete();
        $this->cleanupEntities[1]->delete();
        unset($this->cleanupEntities[0], $this->cleanupEntities[1]);
        $status = \Drupal::service('module_installer')
            ->uninstall(['media']);
        $this->assertTrue($status);
        parent::tearDown();
    }
}
