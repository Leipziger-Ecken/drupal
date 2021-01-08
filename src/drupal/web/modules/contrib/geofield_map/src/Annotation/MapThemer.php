<?php

namespace Drupal\geofield_map\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a base class for Geofield Map Themer plugin annotations.
 *
 * @Annotation
 */
class MapThemer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Geofield Map Themer plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The Geofield Map Themer plugin Description.
   *
   * @var string
   *
   * This will appear under the options select, once the Map Themer plugin
   * has been chosen by the user.
   */
  public $description;

  /**
   * The Geofield Map Themer plugin type.
   *
   * @var string
   */
  public $type;

  /**
   * Define info regarding the Map Themer Marker Icon Selection Type.
   *
   * @var array
   */
  public $markerIconSelection;

  /**
   * The Geofield Map Themer plugin application context.
   *
   * @var array
   */
  public $context;

  /**
   * Settings for the Themer.
   *
   * @var array
   */
  public $defaultSettings = [];

}
