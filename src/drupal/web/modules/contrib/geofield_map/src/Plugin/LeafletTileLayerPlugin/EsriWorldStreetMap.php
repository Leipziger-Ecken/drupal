<?php

namespace Drupal\geofield_map\Plugin\LeafletTileLayerPlugin;

use Drupal\geofield_map\LeafletTileLayerPluginBase;

/**
 * Provides an Esri_WorldStreetMap Leaflet TileLayer Plugin.
 *
 * @LeafletTileLayerPlugin(
 *   id = "Esri_WorldStreetMap",
 *   label = "Esri WorldStreetMap",
 *   url = "https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}",
 *   options = {
 *     "attribution" = "Tiles &copy; Esri &mdash; Source: Esri, DeLorme,
 * NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong),
 * Esri (Thailand), TomTom, 2012",
 *   }
 * )
 */
class EsriWorldStreetMap extends LeafletTileLayerPluginBase {}
