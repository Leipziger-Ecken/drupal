<?php

namespace Drupal\geofield_map\Services;

use Drupal\Component\Serialization\Json;

/**
 * Class GeocoderService.
 */
class GeocoderService {

  /**
   * Get Filtered Js Map Geocoder Settings.
   *
   * @param array $map_geocoder_settings
   *   The raw map_geocoder_settings.
   *
   * @return array
   *   The Filtered map_geocoder_settings ready for Js injection.
   */
  public function getJsGeocoderSettings(array $map_geocoder_settings) {

    // Set the $map_geocoder_settings['providers'] as the enabled providers.
    $enabled_providers = [];
    foreach ($map_geocoder_settings['providers'] as $plugin_id => $plugin) {
      if (!empty($plugin['checked'])) {
        $enabled_providers[] = $plugin_id;
      }
    }
    $map_geocoder_settings['providers'] = $enabled_providers;
    $map_geocoder_settings['options'] = [
      'options' => Json::decode($map_geocoder_settings['options']),
    ];

    return $map_geocoder_settings;
  }

}
