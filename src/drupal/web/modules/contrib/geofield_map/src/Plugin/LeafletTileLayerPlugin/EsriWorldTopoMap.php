<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Esri_WorldTopoMap Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Esri_WorldTopoMap",
 *   label = "Esri WorldTopoMap",
 *   url = "https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}",
 *   options = {
 *     "attribution" = "Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ,
 * TomTom, Intermap, iPC, USGS, FAO, NPS, NRCAN, GeoBase, Kadaster NL,
 *   Ordnance Survey, Esri Japan, METI, Esri China (Hong Kong),
 * and the GIS User Community",
 *   }
 * )
 */
class EsriWorldTopoMap extends LeafletTileLayerPluginBase {}
