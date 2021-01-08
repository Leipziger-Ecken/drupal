<?php

/**
 * @file
 * Hooks provided by the Geofield Map module.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Geofield Map Lat Lon Element Settings.
 *
 * Modules may implement this hook to alter the Settings that are passed into
 * the Geofield Map Element Widget.
 *
 * @param array $map_settings
 *   The array of geofield map element settings.
 * @param array $complete_form
 *   The complete form array.
 * @param array $form_state_values
 *   The form state values array.
 */
function hook_geofield_map_latlon_element_alter(array &$map_settings, array &$complete_form, array &$form_state_values) {
  // Make custom alterations to $map_settings, eventually using $complete_form
  // and $form_state_values contexts.
}

/**
 * Alter the Geofield Map Google Maps Formatter settings.
 *
 * Allow other modules to add/alter the map js settings.
 *
 * @param array $map_settings
 *   The array of geofield map element settings.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   The field values to be rendered.
 * */
function hook_geofield_map_googlemap_formatter_alter(array &$map_settings, FieldItemListInterface &$items) {
  // Make custom alterations to $map_settings, eventually using $items context.
}

/**
 * Alter the Geofield Map Google Maps View Style settings.
 *
 * Allow other modules to add/alter the map js settings.
 *
 * @param array $map_settings
 *   The array of geofield map element settings.
 * @param \Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle $view_style
 *   The Geofield Google Map View Style.
 * */
function hook_geofield_map_googlemap_view_style_alter(array &$map_settings, GeofieldGoogleMapViewStyle &$view_style) {
  // Make custom alterations to $map_settings, eventually using the $view_style
  // context.
}

/**
 * @} End of "addtogroup hooks".
 */
