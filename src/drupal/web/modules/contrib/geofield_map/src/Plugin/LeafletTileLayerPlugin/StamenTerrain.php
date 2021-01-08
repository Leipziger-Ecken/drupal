<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Stamen_Terrain Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Stamen_Terrain",
 *   label = "Stamen Terrain",
 *   url = "http://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}.{ext}",
 *   options = {
 *     "minZoom" = 0,
 *     "maxZoom" = 18,
 *     "ext" = "png",
 *     "subdomains" = "abcd",
 *     "attribution" = "Map tiles by
 * <a href='http://stamen.com'>Stamen Design</a>,
 *   <a href='http://creativecommons.org/licenses/by/3.0'>CC BY 3.0</a>
 *   &mdash; Map data &copy;
 *   <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a>",
 *   }
 * )
 */
class StamenTerrain extends LeafletTileLayerPluginBase {}
