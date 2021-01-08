<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Hydda_Full Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Hydda_Full",
 *   label = "Hydda Full",
 *   url = "https://{s}.tile.openstreetmap.se/hydda/full/{z}/{x}/{y}.png",
 *   options = {
 *     "maxZoom" = 18,
 *     "attribution" =
 *   "Tiles courtesy of
 * <a href='http://openstreetmap.se/' target='_blank'>OpenStreetMap Sweden</a>
 *   &mdash; Map data &copy;
 *   <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a>"
 *   }
 * )
 */
class HyddaFull extends LeafletTileLayerPluginBase {}
