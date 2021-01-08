<?php

namespace Drupal\geofield_map;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\geofield_map\Annotation\MapThemer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a plugin manager for Geofield Map Themers.
 */
class MapThemerPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructor of the a Geofield Map Themers plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $translation_manager,
    EntityTypeManagerInterface $entity_manager
  ) {
    parent::__construct('Plugin/GeofieldMapThemer', $namespaces, $module_handler, MapThemerInterface::class, MapThemer::class);

    $this->alterInfo('geofield_map_themer_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_themer_plugins');
    $this->entityManager = $entity_manager;
    $this->geofieldMapSettings = $config_factory->get('geofield_map.settings');
  }

  /**
   * Generate a by weight ordered Options array for all the MapThemers plugins.
   *
   * @param string $context
   *   Context value to filter the MapThemers plugins with.
   *
   * @return mixed[]
   *   An array of MapThemers plugins Options. Keys are plugin IDs.
   */
  public function getMapThemersList($context = NULL) {
    $options = [];
    $map_themers_definitions = $this->getDefinitions();
    uasort($map_themers_definitions, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    foreach ($map_themers_definitions as $k => $map_themer) {
      if (empty($context) || in_array($context, $map_themer['context'])) {
        /* @var \Drupal\Core\StringTranslation\TranslatableMarkup $map_themer_name */
        $map_themer_name = $map_themer['name'];
        $options[$k] = $map_themer_name->render();
      }
    }
    return $options;
  }

}
