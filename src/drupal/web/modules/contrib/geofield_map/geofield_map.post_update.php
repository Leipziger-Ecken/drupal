<?php

/**
 * @file
 * Contains hook_post_update_NAME() implementations for geofield_map.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Re-calculate formatter dependencies.
 */
function geofield_map_post_update_recalculate_formatter_dependencies(&$sandbox) {
  // In geofield_map_update_8201() we may have enabled the geofield_map_extras
  // submodule in case this site is using the formatter that moved there. If
  // that happened, we also need to update the config dependencies so that the
  // new provider module is stored as a dependency. By just loading,
  // recalculating, and re-saving, we ensure that process happens. We do this in
  // a post-update hook to avoid using entity API in hook_update_N().
  if (!\Drupal::moduleHandler()->moduleExists('geofield_map_extras')) {
    return;
  }
  drupal_flush_all_caches();
  $map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('geofield');
  $config_factory = \Drupal::configFactory();
  foreach ($map as $entity_type_id => $info) {
    foreach ($info as $field_name => $data) {
      foreach ($data['bundles'] as $bundle_name) {
        $displays = $config_factory->listAll("core.entity_view_display.{$entity_type_id}.{$bundle_name}.");
        foreach ($displays as $display_name) {
          $id = substr($display_name, 25);
          /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $display */
          $display = EntityViewDisplay::load($id);
          if ($display) {
            $component = $display->getComponent($field_name);
            if (!empty($component['type']) && $component['type'] === 'geofield_static_google_map') {
              $display->calculateDependencies()
                ->save();
              \Drupal::messenger()->addStatus(t('Updated dependencies for @display_name .', [
                '@display_name' => $display_name,
              ]));
            }
          }
        }
      }
    }
  }
}
