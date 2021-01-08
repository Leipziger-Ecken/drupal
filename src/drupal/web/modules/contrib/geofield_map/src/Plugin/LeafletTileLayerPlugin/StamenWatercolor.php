<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Stamen_Watercolor Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Stamen_Watercolor",
 *   label = "Stamen Watercolor",
 *   url = "http://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}",
 *   options = {
 *     "minZoom" = 1,
 *     "maxZoom" = 16,
 *     "ext" = "png",
 *     "subdomains" = "abcd",
 *     "attribution" = "Map tiles by
 *   <a href='http://stamen.com'>Stamen Design</a>,
 *   <a href='http://creativecommons.org/licenses/by/3.0'>CC BY 3.0</a>
 *   &mdash; Map data &copy;
 *   <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a>",
 *   }
 * )
 */
class StamenWatercolor extends LeafletTileLayerPluginBase {}
