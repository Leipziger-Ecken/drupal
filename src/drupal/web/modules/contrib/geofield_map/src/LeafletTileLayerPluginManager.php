<?php

namespace Drupal\geofield_map;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the Leaflet tile layers plugin plugin manager.
 */
class LeafletTileLayerPluginManager extends DefaultPluginManager {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new LeafletTileLayerPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, RequestStack $request_stack) {
    parent::__construct('Plugin/LeafletTileLayerPlugin', $namespaces, $module_handler, 'Drupal\geofield_map\LeafletTileLayerPluginInterface', 'Drupal\geofield_map\Annotation\LeafletTileLayerPlugin');

    $this->alterInfo('geofield_map_leaflet_tile_layer_plugin_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_leaflet_tile_layer_plugin_plugins');
    $this->requestStack = $request_stack;
  }

  /**
   * Get the associative array of all defined Leaflet Tile Layers.
   */
  public function getLeafletTileLayers() {

    $leaflet_tile_layers = [];
    foreach ($this->getDefinitions() as $k => $plugin) {

      // Change tile url protocol if under secure request (Ssl).
      $plugin['url'] = $this->requestStack->getCurrentRequest()->isSecure() ? preg_replace("/^http:/i", "https:", $plugin['url']) : $plugin['url'];
      $leaflet_tile_layers[$k] = [
        'label' => $plugin['label'],
        'url' => $plugin['url'],
        'options' => $plugin['options'],
      ];
    }
    ksort($leaflet_tile_layers);
    return $leaflet_tile_layers;
  }

  /**
   * Get the id => label associative array of all defined Leaflet Tile Layers.
   */
  public function getLeafletTilesLayersOptions() {
    $options = [];
    foreach ($this->getDefinitions() as $k => $plugin) {
      $options[$k] = $plugin['label'];
    }
    ksort($options);
    return $options;
  }

}
