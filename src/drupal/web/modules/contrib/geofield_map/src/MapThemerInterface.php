<?php

namespace Drupal\geofield_map;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for Geofield Map Themers plugins.
 *
 * Geofield Map Themers are plugins that allow to differentiate map elements
 * (markers, poly-lines, polygons) based on specific dynamic logics .
 */
interface MapThemerInterface extends PluginInspectionInterface {

  /**
   * Get the MapThemer name property.
   *
   * @return string
   *   The MapThemer name.
   */
  public function getName();

  /**
   * Get the MapThemer description property.
   *
   * @return string
   *   The MapThemer description.
   */
  public function getDescription();

  /**
   * Get the defaultSettings for the Map Themer Plugin.
   *
   * @param string $k
   *   A specific defaultSettings key index.
   *
   * @return array|string
   *   The defaultSettings to be returned.
   */
  public function defaultSettings($k = NULL);

  /**
   * Provides a Map Themer Options Element.
   *
   * @param array $defaults
   *   The default values/settings.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle $geofieldMapView
   *   The Geofield Map View display object.
   *
   * @return array
   *   The Map Themer Options Element
   */
  public function buildMapThemerElement(array $defaults, array &$form, FormStateInterface $form_state, GeofieldGoogleMapViewStyle $geofieldMapView);

  /**
   * Retrieve the icon for theming definition.
   *
   * @param array $datum
   *   The geometry feature array definition.
   * @param \Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle $geofieldMapView
   *   The Geofield Map View dispaly object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity generating the datum.
   * @param mixed $map_theming_values
   *   The Map themer mapping values.
   *
   * @return mixed
   *   The icon definition.
   */
  public function getIcon(array $datum, GeofieldGoogleMapViewStyle $geofieldMapView, EntityInterface $entity, $map_theming_values);

  /**
   * Generate the Legend render array.
   *
   * @param array $map_theming_values
   *   The Map themer mapping values.
   * @param array $configuration
   *   The legend block configuration array.
   *
   * @return mixed
   *   The icon definition.
   */
  public function getLegend(array $map_theming_values, array $configuration = []);

}
