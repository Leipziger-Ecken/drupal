<?php

namespace Drupal\le_migrate_media\Commands;

use Drush\Commands\DrushCommands;
use Drupal\media\Entity\Media;

/**
* A Drush commandfile.
*
* In addition to this file, you need a drush.services.yml
* in root of your module, and a composer.json file that provides the name
* of the services file to use.
*/
class MigrateMediaCommands extends DrushCommands {
  /**
  * Migrates image fields to media fields
  *
  * @command le_migrate_media:image-to-media
  * @option overwrite
  *   Overwrite existing media files
  */
  public function migrateImageToMedia($options = ['overwrite' => false]) 
  {
    $mapping = [
      'le_akteur' => [
        'field_le_akteur_image' => 'field_logo',
      ],
      'le_event' => [
        'field_le_event_image' => 'field_main_image',
      ],
    ];

    foreach($mapping as $content_type => $field_mapping) {
      $this->migrateContentType($content_type, $field_mapping, !$options['overwrite']);
    }
  }

  protected function migrateContentType($content_type, array $field_mapping, $skip_existing = true) 
  {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $content_type);
    $result = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    foreach($result as $nid) {
      $node = $node_storage->load($nid);

      foreach($field_mapping as $image_field => $media_field) {
        $this->migrateNodeImageField($node, $image_field, $media_field, $skip_existing);
      }
    }
  }

  protected function migrateNodeImageField($node, $image_field, $media_field, $skip_existing = true)
  {
    $image_fid = $node->get($image_field);

    if (
      isset($image_fid) && 
      isset($image_fid[0]) && 
      isset($image_fid[0]->target_id)
    ) {
      $image_fid = $image_fid[0]->target_id;
    } else {
      return;
    }
    
    // check if media is already migrated
    $media_id = $node->get($media_field);
    if (
      isset($media_id) &&
      isset($media_id[0]) &&
      isset($media_id[0]->target_id)
    ) {
      // if media already exists, we can skip
      if ($skip_existing) {
        return;
      } else {
        $media_id =  $media_id[0]->target_id;
      }
    } else {
      $media_id = null;
    }

    $image_file = \Drupal::entityTypeManager()->getStorage('file')->load($image_fid);
    if (!$image_file) {
      return;
    }

    $uid = $image_file->uid[0]->target_id;
    
    $media_entity = $media_id 
    ? Media::load($media_id)
    : Media::create([
      'bundle' => 'image',
      'uid' => $uid, // keep same user
    ]);
    
    $media_entity
    ->set('field_media_image', $image_file->id())
    ->setName($image_file->getFilename())
    ->setPublished(true)
    ->save();

    $node->set($media_field, $media_entity->id());
    $node->save();
  }

}
