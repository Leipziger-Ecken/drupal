<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an OpenMapSurfer_Roads Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "OpenMapSurfer_Roads",
 *   label = "OpenMapSurfer Roads",
 *   url = "https://maps.heigit.org/openmapsurfer/tiles/roads/webmercator/{z}/{x}/{y}.png",
 *   options = {
 *     "maxZoom" = 19,
 *     "attribution" = "Imagery from
 *   <a href='http://giscience.uni-hd.de/'>GIScience Research Group
 *   @ University of Heidelberg</a> | Map data &copy;
 *   <a href='https://www.openstreetmap.org/copyright'>OpenStreetMap</a>
 *   contributors",
 *   }
 * )
 */
class OpenMapSurferRoads extends LeafletTileLayerPluginBase {}
