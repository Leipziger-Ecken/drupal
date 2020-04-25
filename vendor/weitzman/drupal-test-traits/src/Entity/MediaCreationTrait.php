<?php

namespace weitzman\DrupalTestTraits\Entity;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Provides a trait to create media which are tracked for deletion.
 */
trait MediaCreationTrait
{
    use MediaTypeCreationTrait {
        createMediaType as coreCreateMediaType;
    }

    /**
     * {@inheritdoc}
     */
    protected function createMediaType($source_plugin_id, array $values = [])
    {
        $entity = $this->coreCreateMediaType($source_plugin_id, $values);
        $this->markEntityForCleanup($entity);
        return $entity;
    }

    /**
     * Creates a media entity and marks it for automatic cleanup.
     *
     * @param array $settings
     * @return \Drupal\media\MediaInterface
     */
    protected function createMedia(array $settings = [])
    {
        /** @var \Drupal\media\MediaInterface $entity */
        $entity = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->create($settings);
        $entity->save();
        $this->markEntityForCleanup($entity);
        return $entity;
    }
}
