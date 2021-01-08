<?php

namespace Drupal\geofield_map\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a LeafletTileLayerPlugin item annotation object.
 *
 * @see \Drupal\geofield_map\LeafletTileLayerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class LeafletTileLayerPlugin extends Plugin {


  /**
   * The  Leaflet Tile Layer plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The url of the Leaflet Tile Layer.
   *
   * @var string
   */
  public $url;

  /**
   * The options array for the Leaflet Tile Layer plugin.
   *
   * @var array
   */
  public $options = [];

}
