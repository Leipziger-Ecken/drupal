<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an OpenTopoMap Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "OpenTopoMap",
 *   label = "OpenTopoMap",
 *   url = "http://{s}.tile.opentopomap.org/{z}/{x}/{y}.png",
 *   options = {
 *     "maxZoom" = 17,
 *     "attribution" = "Map data: &copy;
 * <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a>,
 *   <a href='http://viewfinderpanoramas.org'>SRTM</a> | Map style: &copy;
 *   <a href='https://opentopomap.org'>OpenTopoMap</a>
 *   (<a href='https://creativecommons.org/licenses/by-sa/3.0/'>CC-BY-SA</a>)",
 *   }
 * )
 */
class OpenTopoMap extends LeafletTileLayerPluginBase {}
