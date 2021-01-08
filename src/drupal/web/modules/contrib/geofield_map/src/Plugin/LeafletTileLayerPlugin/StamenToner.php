<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Stamen_Toner Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Stamen_Toner",
 *   label = "Stamen Toner",
 *   url = "http://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}.{ext}",
 *   options = {
 *     "minZoom" = 0,
 *     "maxZoom" = 20,
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
class StamenToner extends LeafletTileLayerPluginBase {}
