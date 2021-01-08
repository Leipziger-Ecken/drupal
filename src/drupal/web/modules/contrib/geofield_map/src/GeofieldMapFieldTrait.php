<?php

namespace Drupal\geofield_map;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common functions for Geofield Map fields.
 *
 * @package Drupal\geofield_map
 */
trait GeofieldMapFieldTrait {

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $gMapTypesOptions = [
    'roadmap' => 'Roadmap',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
    'terrain' => 'Terrain',
  ];

  /**
   * Infowindow Field Types Options.
   *
   * @var array
   */
  protected $infowindowFieldTypesOptions = [
    'string_long',
    'string',
    'text',
    'text_long',
    "text_with_summary",
  ];

  /**
   * Geofield Map Controls Positions Options.
   *
   * @var array
   */
  protected $controlPositionsOptions = [
    'TOP_LEFT' => 'Top Left',
    'TOP_RIGHT' => 'Top Right',
    'BOTTOM_LEFT' => 'Bottom Left',
    'BOTTOM_RIGHT' => 'Bottom Right',
  ];


  /**
   * Custom Map Style Placeholder.
   *
   * @var string
   */
  protected $customMapStylePlaceholder = '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0e1626"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#4e6d70"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]}]';

  /**
   * The FieldDefinition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * Get the GMap Api Key from the geofield_map.google_maps service.
   *
   * @return string
   *   The GMap Api Key
   */
  private function getGmapApiKey() {
    return \Drupal::service('geofield_map.google_maps')->getGmapApiKey();
  }

  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'gmap_api_key' => '',
      'map_dimensions' => [
        'width' => '100%',
        'height' => '450px',
      ],
      'map_empty' => [
        'empty_behaviour' => '0',
        'empty_message' => t('No Geofield Value entered for this field'),
      ],
      'map_center' => [
        'lat' => '42',
        'lon' => '12.5',
        'center_force' => 0,
      ],
      'map_zoom_and_pan' => [
        'zoom' => [
          'initial' => 6,
          'force' => 0,
          'min' => 1,
          'max' => 22,
          'finer' => 0,
        ],
        'scrollwheel' => 1,
        'draggable' => 1,
        'map_reset' => 0,
        'map_reset_position' => 'TOP_RIGHT',
      ],
      'map_controls' => [
        'disable_default_ui' => 0,
        'zoom_control' => 1,
        'map_type_id' => 'roadmap',
        'map_type_control' => 1,
        'map_type_control_options_type_ids' => [
          'roadmap' => 'roadmap',
          'satellite' => 'satellite',
          'hybrid' => 'hybrid',
          'terrain' => 'terrain',
        ],
        'scale_control' => 1,
        'street_view_control' => 1,
        'fullscreen_control' => 1,
      ],
      'map_marker_and_infowindow' => [
        'icon_image_mode' => 'icon_file',
        'icon_image_path' => '',
        'icon_file_wrapper' => [
          'icon_file' => '',
        ],
        'infowindow_field' => 'title',
        'view_mode' => 'full',
        'multivalue_split' => 0,
        'force_open' => 0,
        'tooltip_field' => 'title',
      ],
      'map_oms' => [
        'map_oms_control' => 1,
        'map_oms_options' => '{"markersWontMove": "true", "markersWontHide": "true", "basicFormatEvents": "true", "nearbyDistance": 3}',
      ],
      'map_additional_options' => '',
      'map_additional_libraries' => [],
      'map_geometries_options' => '{"strokeColor":"black","strokeOpacity":"0.8","strokeWeight":2,"fillColor":"blue","fillOpacity":"0.1", "clickable": false}',
      'custom_style_map' => [
        'custom_style_control' => 0,
        'custom_style_name' => '',
        'custom_style_options' => '',
        'custom_style_default' => 0,
      ],
      'map_markercluster' => [
        'markercluster_control' => 0,
        'markercluster_additional_options' => '{"maxZoom":12, "gridSize":50}',
      ],
      'map_geocoder' => [
        'control' => 0,
        'settings' => [
          'position' => 'topright',
          'input_size' => 25,
          'providers' => [],
          'min_terms' => 4,
          'delay' => 800,
          'zoom' => 16,
          'infowindow' => 0,
          'options' => '',
        ],
      ],
      'map_lazy_load' => [
        'lazy_load' => 0,
      ],
    ];
  }

  /**
   * Generate the Google Map Settings Form.
   */
  private function getMapGeocoderTitle() {
    return $this->t('Search Address Geocoder');
  }

  /**
   * Generate the Google Map Settings Form.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $settings
   *   Form settings.
   * @param array $default_settings
   *   Default settings.
   *
   * @return array
   *   The GMap Settings Form
   */
  public function generateGmapSettingsForm(array $form, FormStateInterface $form_state, array $settings, array $default_settings) {

    $elements['#attached'] = [
      'library' => [
        'geofield_map/geofield_map_view_display_settings',
      ],
    ];

    $elements = [];

    // Attach Geofield Map Library.
    $elements['#attached']['library'] = [
      'geofield_map/geofield_map_general',
    ];

    // Set Google Api Key Element.
    $elements['map_google_api_key'] = $this->setMapGoogleApiKeyElement();

    // Set Map Dimension Element.
    $this->setMapDimensionsElement($settings, $elements);

    // Set Map Empty Options Element.
    $this->setMapEmptyElement($settings, $elements);

    $elements['gmaps_api_link_markup'] = [
      '#markup' => $this->t('The following settings comply with the @gmaps_api_link.', [
        '@gmaps_api_link' => $this->link->generate($this->t('Google Maps JavaScript API Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    // Set Map Center Element.
    $this->setMapCenterElement($settings, $elements);

    // Set Map Zoom and Pan Element.
    $this->setMapZoomAndPanElement($settings, $default_settings, $elements);

    // Set Map Control Element.
    $this->setMapControlsElement($settings, $elements);

    // Set Map Marker and Infowindow Element.
    $this->setMapMarkerAndInfowindowElement($form, $settings, $elements);

    // Set Map Additional Options Element.
    $this->setMapAdditionalOptionsElement($settings, $elements);

    // Set Map Geometries Options Element.
    $this->setGeometriesAdditionalOptionsElement($settings, $elements);

    // Set Overlapping Marker Spiderfier Element.
    $this->setMapOmsElement($settings, $default_settings, $elements);

    // Set Custom Map Style Element.
    $this->setCustomStyleMapElement($settings, $elements);

    // Set Map Marker Cluster Element.
    $this->setMapMarkerclusterElement($settings, $elements);

    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    $this->setGeocoderMapControl($elements, $settings);

    // Set Map Lazy Load Element.
    $this->setMapLazyLoad($settings, $elements);

    return $elements;

  }

  /**
   * Pre Process the MapSettings.
   *
   * Performs some preprocess on the maps settings before sending to js.
   *
   * @param array $map_settings
   *   The map settings.
   */
  protected function preProcessMapSettings(array &$map_settings) {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = $this->config;
    $geofield_map_settings = $config->getEditable('geofield_map.settings');

    // Set the gmap_api_key as map settings.
    $map_settings['gmap_api_key'] = $this->getGmapApiKey();

    // Geofield Map Google Maps and Geocoder Settings.
    $map_settings['gmap_api_localization'] = $this->googleMapsService->getGmapApiLocalization($geofield_map_settings->get('gmap_api_localization'));

    // Transform into simple array values the map_type_control_options_type_ids.
    $map_settings['map_controls']['map_type_control_options_type_ids'] = array_keys(array_filter($map_settings['map_controls']['map_type_control_options_type_ids'], function ($value) {
      return $value !== 0;
    }));

    // Generate Absolute icon_image_path, if it is not.
    $icon_image_path = $map_settings['map_marker_and_infowindow']['icon_image_path'];
    if (!empty($icon_image_path) && !UrlHelper::isExternal($map_settings['map_marker_and_infowindow']['icon_image_path'])) {
      $map_settings['map_marker_and_infowindow']['icon_image_path'] = Url::fromUri('base:', ['absolute' => TRUE])->toString() . $icon_image_path;
    }
  }

  /**
   * Transform Geofield data into Geojson features.
   *
   * @param mixed $items
   *   The Geofield Data Values.
   * @param int $entity_id
   *   The Entity Id.
   * @param string $description
   *   The description value.
   * @param string $tooltip
   *   The tooltip value.
   * @param array $additional_data
   *   Additional data to be added to the feature properties, i.e.
   *   GeofieldGoogleMapViewStyle will add row fields (already rendered).
   *
   * @return array
   *   The data array for the current feature, including Geojson and additional
   *   data.
   */
  protected function getGeoJsonData($items, $entity_id, $description = NULL, $tooltip = NULL, array $additional_data = NULL) {
    $data = [];

    foreach ($items as $delta => $item) {
      $value = ($item instanceof GeofieldItem) ? $item->value : $item;

      try {
        $geometry = $this->geoPhpWrapper->load($value);
      }
      catch (\Exception $exception) {
        $geometry = FALSE;
      }

      if ($geometry instanceof \Geometry) {
        $datum = [
          "type" => "Feature",
          "geometry" => json_decode($geometry->out('json')),
        ];
        $datum['properties'] = [
          // If a multivalue field value with the same index exist, use this,
          // else use the first item as fallback.
          'description' => isset($description[$delta]) ? $description[$delta] : (isset($description[0]) ? $description[0] : NULL),
          'tooltip' => $tooltip,
          'data' => $additional_data,
          'entity_id' => $entity_id,
        ];

        $data[] = $datum;
      }
    }

    return $data;
  }

  /**
   * Set Map Google Api Key Element.
   */
  private function setMapGoogleApiKeyElement() {
    $gmap_api_key = $this->getGmapApiKey();

    $query = [];
    if (isset($this->fieldDefinition)) {
      $query['destination'] = Url::fromRoute('<current>')->toString();
    }

    $this_class = $class = get_class($this);
    // Define the Google Maps API Key value message markup.
    if (!empty($gmap_api_key)) {
      $map_google_api_key_value = $this->t("<strong>Gmap Api Key:</strong> @gmaps_api_key_link", [
        '@gmaps_api_key_link' => $this->link->generate($gmap_api_key, Url::fromRoute('geofield_map.settings', [], [
          'query' => $query,
        ])),
      ]);
      switch ($this_class) {
        case 'Drupal\geofield_map\Plugin\Field\FieldWidget\GeofieldMapWidget':
          // Set the 'map_google_places' accordingly the
          // search_address_geocoder_module title.
          $search_address_geocoder_option_title = $this->getMapGeocoderTitle();
          $gmap_api_key_description = $this->t("<div class='description'>A valid Gmap Api Key is needed for the Widget Google Maps Library and the Geocode & ReverseGeocode functionalities<br>(provided by the Google Maps Geocoder, if the \"@search_address_geocoder_option_title\" option is not selected).</div>", [
            '@search_address_geocoder_option_title' => $search_address_geocoder_option_title,
          ]);
          break;

        case 'Drupal\geofield_map\Plugin\Field\FieldFormatter\GeofieldGoogleMapFormatter':
        case 'Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle':
        case 'Drupal\geofield_map_extras\Plugin\Field\FieldFormatter\GeofieldGoogleEmbedMapFormatter':
        case 'Drupal\geofield_map_extras\Plugin\Field\FieldFormatter\GeofieldGoogleStaticMapFormatter':
          $gmap_api_key_description = $this->t("<div class='description'>A valid Gmap Api Key is needed for Google Maps rendering.</div>");
          break;

        default:
          $gmap_api_key_description = "";
      }
    }
    else {
      $map_google_api_key_value = $this->t("<span class='geofield-map-warning'>Gmap Api Key missing - @settings_page_link.</span>", [
        '@settings_page_link' => $this->link->generate($this->t('Set it in the Geofield Map Configuration Page'), Url::fromRoute('geofield_map.settings', [], [
          'query' => [
            'destination' => Url::fromRoute('<current>')
              ->toString(),
          ],
        ])),
      ]);
      switch ($this_class) {
        case 'Drupal\geofield_map\Plugin\Field\FieldWidget\GeofieldMapWidget':
          $gmap_api_key_description = $this->t("<div class='description'>Google Maps rendering and Geocode & ReverseGeocode functionalities (provided by the Google Maps Geocoder) not available.</div>");
          break;

        case 'Drupal\geofield_map\Plugin\Field\FieldFormatter\GeofieldGoogleMapFormatter':
        case 'Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle':
        case 'Drupal\geofield_map_extras\Plugin\Field\FieldFormatter\GeofieldGoogleEmbedMapFormatter':
        case 'Drupal\geofield_map_extras\Plugin\Field\FieldFormatter\GeofieldGoogleStaticMapFormatter':
          $gmap_api_key_description = $this->t("<div class='geofield-map-warning'>Google Maps rendering not available.</div>");
          break;

        default:
          $gmap_api_key_description = "";
      }
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $map_google_api_key_value,
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $gmap_api_key_description,
      ],
    ];
  }

  /**
   * Set Map Dimension Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapDimensionsElement(array $settings, array &$elements) {
    $elements['map_dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    ];

    $elements['map_dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $settings['map_dimensions']['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
    $elements['map_dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $settings['map_dimensions']['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
  }

  /**
   * Set Map Empty Options Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapEmptyElement(array $settings, array &$elements) {
    $elements['map_empty'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Which behavior in case of empty results?'),
      '#description' => $this->t('If there are no entries on the map, what should be the output?'),
    ];

    if (isset($this->fieldDefinition)) {
      $elements['map_empty']['empty_behaviour'] = [
        '#type' => 'select',
        '#title' => $this->t('Behaviour'),
        '#default_value' => $settings['map_empty']['empty_behaviour'],
        '#options' => $this->emptyMapOptions,
      ];
      $elements['map_empty']['empty_message'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Empty Map Message'),
        '#description' => $this->t('The message that should be rendered instead on an empty map.'),
        '#default_value' => $settings['map_empty']['empty_message'],
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_empty][empty_behaviour]"]' => ['value' => '1'],
          ],
        ],
      ];
    }
    else {
      $elements['map_empty']['#description'] = $this->t('If there are no results from the View query, what should be the output?');
      $elements['map_empty']['empty_behaviour'] = [
        '#type' => 'select',
        '#title' => $this->t('Behaviour'),
        '#default_value' => $settings['map_empty']['empty_behaviour'],
        '#options' => $this->emptyMapOptions,
      ];
    }
  }

  /**
   * Set Map Center Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapCenterElement(array $settings, array &$elements) {
    $elements['map_center'] = [
      '#type' => 'geofield_latlon',
      '#title' => $this->t('Default Center'),
      '#default_value' => $settings['map_center'],
      '#size' => 25,
      '#description' => $this->t('If there are no entries on the map, where should the map be centered?'),
      '#geolocation' => TRUE,
      'center_force' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Force the Map Center'),
        '#description' => $this->t('The Map will generally focus center on the input Geofields.<br>This option will instead force the Map Center notwithstanding the Geofield Values'),
        '#default_value' => $settings['map_center']['center_force'],
        '#return_value' => 1,
      ],
    ];
  }

  /**
   * Set Map Zoom and Pan Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $default_settings
   *   The default_settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapZoomAndPanElement(array $settings, array $default_settings, array &$elements) {

    if (isset($this->fieldDefinition)) {
      $force_center_selector = ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_center][center_force]"]';
      $force_zoom_selector = ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_zoom_and_pan][zoom][force]"]';
      $map_reset_selector = ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_zoom_and_pan][map_reset]"]';
    }
    else {
      $force_center_selector = ':input[name="style_options[map_center][center_force]"]';
      $force_zoom_selector = ':input[name="style_options[map_zoom_and_pan][zoom][force]"]';
      $map_reset_selector = ':input[name="style_options[map_zoom_and_pan][map_reset]"]';
    }

    $elements['map_zoom_and_pan'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Zoom and Pan'),
    ];
    $elements['map_zoom_and_pan']['zoom'] = [
      'initial' => [
        '#type' => 'number',
        '#min' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Start Zoom'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['initial'],
        '#description' => $this->t('The Initial Zoom level of the Google Map.'),
        '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
      ],
      'force' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Force the Start Zoom'),
        '#description' => $this->t('In case of multiple GeoMarkers, the Map will naturally focus zoom on the input Geofields bounds.<br>This option will instead force the Map Zoom on the input Start Zoom value'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['force'],
        '#return_value' => 1,
        '#states' => [
          'visible' => [
            $force_center_selector => ['checked' => FALSE],
          ],
        ],
      ],
      'min' => [
        '#type' => 'number',
        '#min' => isset($default_settings['map_zoom_and_pan']['default']) ? $default_settings['map_zoom_and_pan']['default']['zoom']['min'] : $default_settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Min Zoom Level'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#description' => $this->t('The Minimum Zoom level for the Map.'),
      ],
      'max' => [
        '#type' => 'number',
        '#min' => $settings['map_zoom_and_pan']['zoom']['min'],
        '#max' => isset($default_settings['map_zoom_and_pan']['default']) ? $default_settings['map_zoom_and_pan']['default']['zoom']['max'] : $default_settings['map_zoom_and_pan']['zoom']['max'],
        '#title' => $this->t('Max Zoom Level'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['max'],
        '#description' => $this->t('The Maximum Zoom level for the Map.'),
        '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
      ],
      'finer' => [
        '#title' => $this->t('Zoom Finer'),
        '#type' => 'number',
        '#max' => 3,
        '#min' => -3,
        '#step' => 1,
        '#description' => $this->t('Value that might/will be added to default Fit Markers Bounds Zoom. (-3 / +3)'),
        '#default_value' => $settings['map_zoom_and_pan']['zoom']['finer'] ?? $this->defaultSettings['map_zoom_and_pan']['zoom']['finer'],
        '#states' => [
          'invisible' => [
            $force_zoom_selector => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $elements['map_zoom_and_pan']['gestureHandling'] = [
      '#type' => 'select',
      '#title' => $this->t('Gesture Handling (Controlling Zoom and Pan)'),
      '#options' => [
        'auto' => 'auto',
        'greedy' => 'greedy',
        'cooperative' => 'cooperative',
        'none' => 'none',
      ],
      '#default_value' => isset($settings['map_zoom_and_pan']['gestureHandling']) ? $settings['map_zoom_and_pan']['gestureHandling'] : 'auto',
      '#description' => $this->t("This control sets how users can zoom and pan the map, and also whether the user's page scrolling actions take priority over the map's zooming and panning.<br>Visit the @google_map_page to inspect and learn the corresponding behaviours of the different options.", [
        '@google_map_page' => $this->link->generate(t("Official Google Maps Javascript API 'Controlling Zoom and Pan' page"), Url::fromUri('https://developers.google.com/maps/documentation/javascript/interaction', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];
    $elements['map_zoom_and_pan']['scrollwheel'] = [
      '#type' => 'hidden',
      '#default_value' => $settings['map_zoom_and_pan']['scrollwheel'],
    ];
    $elements['map_zoom_and_pan']['draggable'] = [
      '#type' => 'hidden',
      '#default_value' => $settings['map_zoom_and_pan']['draggable'],
    ];

    $elements['map_zoom_and_pan']['map_reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Map Reset Control'),
      '#description' => $this->t('This will show a "Reset Map" button to reset the Map to its initial center & zoom state'),
      '#default_value' => isset($settings['map_zoom_and_pan']['map_reset']) ? $settings['map_zoom_and_pan']['map_reset'] : 0,
      '#return_value' => 1,
    ];

    $elements['map_zoom_and_pan']['map_reset_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Map Reset Control Position'),
      '#options' => $this->controlPositionsOptions,
      '#default_value' => isset($settings['map_zoom_and_pan']['map_reset_position']) ? $settings['map_zoom_and_pan']['map_reset_position'] : 'TOP_RIGHT',
      '#states' => [
        'visible' => [
          $map_reset_selector => ['checked' => TRUE],
        ],
      ],
    ];

  }

  /**
   * Set Map Control Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapControlsElement(array $settings, array &$elements) {
    if (isset($this->fieldDefinition)) {
      $disable_default_ui_selector = ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_controls][disable_default_ui]"]';
    }
    else {
      $disable_default_ui_selector = ':input[name="style_options[map_controls][disable_default_ui]"]';
    }

    $elements['map_controls'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Controls'),
    ];
    $elements['map_controls']['disable_default_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Default UI'),
      '#description' => $this->t('This property disables any automatic UI behavior and Control from the Google Map'),
      '#default_value' => $settings['map_controls']['disable_default_ui'],
      '#return_value' => 1,
    ];
    $elements['map_controls']['zoom_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zoom Control'),
      '#description' => $this->t('The enabled/disabled state of the Zoom control.'),
      '#default_value' => $settings['map_controls']['zoom_control'],
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          $disable_default_ui_selector => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['map_controls']['map_type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Map Type'),
      '#default_value' => $settings['map_controls']['map_type_id'],
      '#options' => $this->gMapTypesOptions,
    ];
    $elements['map_controls']['map_type_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled Map Type Control'),
      '#description' => $this->t('The initial enabled/disabled state of the Map type control.'),
      '#default_value' => $settings['map_controls']['map_type_control'],
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          $disable_default_ui_selector => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['map_controls']['map_type_control_options_type_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The enabled Map Types'),
      '#description' => $this->t('The Map Types that will be available in the Map Type Control.'),
      '#default_value' => $settings['map_controls']['map_type_control_options_type_ids'],
      '#options' => $this->gMapTypesOptions,
      '#return_value' => 1,
    ];

    if (isset($this->fieldDefinition)) {
      $elements['map_controls']['map_type_control_options_type_ids']['#states'] = [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_controls][map_type_control]"]' => ['checked' => FALSE]],
          [$disable_default_ui_selector => ['checked' => TRUE]],
        ],
      ];
    }
    else {
      $elements['map_controls']['map_type_control_options_type_ids']['#states'] = [
        'invisible' => [
          [':input[name="style_options[map_controls][map_type_control]"]' => ['checked' => FALSE]],
          [$disable_default_ui_selector => ['checked' => TRUE]],
        ],
      ];
    }

    $elements['map_controls']['scale_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scale Control'),
      '#description' => $this->t('Show map scale'),
      '#default_value' => $settings['map_controls']['scale_control'],
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          $disable_default_ui_selector => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['map_controls']['street_view_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Streetview Control'),
      '#description' => $this->t('Enable the Street View functionality on the Map.'),
      '#default_value' => $settings['map_controls']['street_view_control'],
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          $disable_default_ui_selector => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['map_controls']['fullscreen_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fullscreen Control'),
      '#description' => $this->t('Enable the Fullscreen View of the Map.'),
      '#default_value' => $settings['map_controls']['fullscreen_control'],
      '#return_value' => 1,
      '#states' => [
        'visible' => [
          $disable_default_ui_selector => ['checked' => FALSE],
        ],
      ],
    ];
  }

  /**
   * Set Map Marker and Infowindow Element.
   *
   * @param array $form
   *   The Form array.
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapMarkerAndInfowindowElement(array $form, array $settings, array &$elements) {

    $icon_image_path_description = $this->t('Input the Specific Icon Image path (absolute path, or relative to the Drupal site root if not prefixed with the initial slash).');
    $icon_image_path_description .= '<br>' . $this->t('Can be an absolute or relative URL.');
    $token_replacement_disclaimer = $this->t('<b>Note: </b> Using <strong>Replacement Patterns</strong> it is possible to dynamically define the Marker Icon output, with the composition of Marker Icon paths including entity properties or fields values.');
    $icon_image_path_description .= '<br>' . $token_replacement_disclaimer;
    $twig_link = $this->link->generate('Twig', Url::fromUri('http://twig.sensiolabs.org/documentation', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ])
    );
    $icon_image_path_description .= '<br>' . $this->t('You may include @twig_link.', [
      '@twig_link' => $twig_link,
    ]);

    $elements['map_marker_and_infowindow'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Marker and Infowindow'),
      '#prefix' => '<div id="map-marker-and-infowindow-wrapper">',
      '#suffix' => '</div>',
    ];
    $elements['map_marker_and_infowindow']['icon_image_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Image Path'),
      '#size' => '120',
      '#description' => $icon_image_path_description,
      '#default_value' => $settings['map_marker_and_infowindow']['icon_image_path'],
      '#placeholder' => 'modules/contrib/geofield_map/images/beachflag.png',
      '#element_validate' => [[get_class($this), 'urlValidate']],
      '#weight' => -10,
    ];

    $multivalue_fields_states = [];

    $entities_fields_options = [];
    foreach ($this->infowindowFieldTypesOptions as $field_type) {
      $entities_fields_options = array_merge_recursive($entities_fields_options, $this->entityFieldManager->getFieldMapByFieldType($field_type));
    }

    // Setup the tokens for views fields.
    // Code is snatched from Drupal\views\Plugin\views\field\FieldPluginBase.
    if (!isset($this->fieldDefinition)) {
      $elements['map_marker_and_infowindow']['icon_image_path']['#description'] .= '<br>' . $this->t('Twig notation allows you to define per-row icons (@see this @icon_image_path_issue).', [
        '@icon_image_path_issue' => $this->link->generate('Geofield Map drupal.org issue', Url::fromUri('https://www.drupal.org/project/geofield_map/issues/3074255', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]);

      $options = [];
      $optgroup_fields = (string) t('Fields');
      if (isset($this->displayHandler)) {
        foreach ($this->displayHandler->getHandlers('field') as $id => $field) {
          /* @var \Drupal\views\Plugin\views\field\EntityField $field */
          $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($field->label(), ":"), 2);
        }
      }

      $replacement_output = [];
      if (!empty($options)) {
        $replacement_output[] = [
          '#markup' => '<p>' . $this->t("The following replacement tokens are available. Fields may be marked as <em>Exclude from display</em> if you prefer.") . '</p>',
        ];
        foreach (array_keys($options) as $type) {
          if (!empty($options[$type])) {
            $items = [];
            foreach ($options[$type] as $key => $value) {
              $items[] = $key;
            }
            $item_list = [
              '#theme' => 'item_list',
              '#items' => $items,
            ];
            $replacement_output[] = $item_list;
          }
        }
      }

      $elements['map_marker_and_infowindow']['help'] = [
        '#type' => 'details',
        '#title' => $this->t('Replacement patterns'),
        '#value' => $replacement_output,
      ];
    }

    // Add SVG UI file support.
    $elements['map_marker_and_infowindow']['icon_image_path']['#description'] .= !$this->moduleHandler->moduleExists('svg_image') ? '<br>' . $this->t('SVG Files support is disabled. Enabled it with @svg_image_link', [
      '@svg_image_link' => $this->link->generate('SVG Image Module', Url::fromUri('https://www.drupal.org/project/svg_image', [
        'absolute' => TRUE,
        'attributes' => ['target' => 'blank'],
      ])),
    ]) : '<br>' . $this->t('SVG Files support enabled.');

    // In case it is a Field Formatter.
    if (isset($this->fieldDefinition)) {

      /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->fieldDefinition->getTargetEntityTypeId();
      // Get the configurations of possible entity fields.
      $fields_configurations = $this->entityFieldManager->getFieldStorageDefinitions($entity);

      $title_options = [
        '0' => $this->t('- Any -'),
        'title' => $this->t('- Title -'),
      ];

      $this_entity_fields_options = $title_options;

      // Get the Cardinality set for the Formatter Field.
      $field_cardinality = $this->fieldDefinition->getFieldStorageDefinition()
        ->getCardinality();

      foreach ($entities_fields_options[$this->fieldDefinition->getTargetEntityTypeId()] as $k => $field) {
        if (!empty(array_intersect($field['bundles'], [$form['#bundle']])) &&
          !in_array($k, ['title', 'revision_log'])) {
          $this_entity_fields_options[$k] = $k;
          /* @var \\Drupal\Core\Field\BaseFieldDefinition $fields_configurations[$k] */
          if ($field_cardinality !== 1 && (isset($fields_configurations[$k]) && $fields_configurations[$k]->getCardinality() !== 1)) {
            $multivalue_fields_states[] = ['value' => $k];
          }
        }
      }

      $info_window_source_options = $this_entity_fields_options;
      // Add the #rendered_entity option.
      $info_window_source_options['#rendered_entity'] = $this->t('- Rendered @entity entity -', ['@entity' => $this->fieldDefinition->getTargetEntityTypeId()]);

      $info_window_source_description = $this->t('Choose an existing string/text type field from which populate the Marker Infowindow.');
    }
    // Else it is a Geofield View Style Format Settings.
    else {
      $fields_configurations = $this->entityFieldManager->getFieldStorageDefinitions($this->entityType);
      $info_window_source_options = isset($settings['infowindow_content_options']) ? $settings['infowindow_content_options'] : [];
      $info_window_source_description = $this->t('Choose an existing field from which populate the Marker Infowindow.');
      foreach ($info_window_source_options as $k => $field) {
        /* @var \\Drupal\Core\Field\BaseFieldDefinition $fields_configurations[$k] */
        if (array_key_exists($k, $fields_configurations) && $fields_configurations[$k]->getCardinality() !== 1) {
          $multivalue_fields_states[] = ['value' => $k];
        }
      }
    }

    $elements['map_marker_and_infowindow']['icon_image_path']['#description'] .= '<br>' . $this->t('If not set, or not found/loadable, the Default Google Marker will be used..');

    if (!empty($info_window_source_options)) {
      $elements['map_marker_and_infowindow']['infowindow_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Marker Infowindow Content from'),
        '#description' => $info_window_source_description,
        '#options' => $info_window_source_options,
        '#default_value' => $settings['map_marker_and_infowindow']['infowindow_field'],
      ];
    }

    $elements['map_marker_and_infowindow']['multivalue_split'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multivalue Field Split (<u>A Multivalue Field as been selected for the Infowindow Content)</u>'),
      '#description' => $this->t('If checked, each field value will be split into each matching infowindow, following the same progressive order<br>(the first value of the field will be used otherwise, or as fallback in case of no match)'),
      '#default_value' => !empty($settings['map_marker_and_infowindow']['multivalue_split']) ? $settings['map_marker_and_infowindow']['multivalue_split'] : 0,
      '#return_value' => 1,
    ];

    if (isset($this->fieldDefinition)) {
      $elements['map_marker_and_infowindow']['multivalue_split']['#description'] = $this->t('If checked, each field value will be split into each matching infowindow / geofield, following the same progressive order<br>(the first value of the field will be used otherwise, or as fallback in case of no match)');
      $elements['map_marker_and_infowindow']['multivalue_split']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_marker_and_infowindow][infowindow_field]"]' => $multivalue_fields_states,
        ],
      ];
    }
    else {
      $elements['map_marker_and_infowindow']['multivalue_split']['#description'] = $this->t('If checked, each field value will be split into each matching infowindow /geofield value (as simple text), following the same progressive order. Note: No rewrite, links or replacements patterns might be applied.<br>(The Multiple Field settings from the View Display will be used otherwise).');
      $elements['map_marker_and_infowindow']['multivalue_split']['#states'] = [
        'visible' => [
          ':input[name="style_options[map_marker_and_infowindow][infowindow_field]"]' => $multivalue_fields_states,
        ],
      ];
    }

    // Assure the view_mode to eventually fallback into the (initially defined)
    // $settings['view_mode'].
    $default_view_mode = !empty($settings['view_mode']) ? $settings['view_mode'] : (!empty($settings['map_marker_and_infowindow']['view_mode']) ? $settings['map_marker_and_infowindow']['view_mode'] : NULL);

    if (isset($this->fieldDefinition)) {
      // Get the human readable labels for the entity view modes.
      $view_mode_options = [];
      foreach ($this->entityDisplayRepository->getViewModes($this->fieldDefinition->getTargetEntityTypeId()) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $elements['map_marker_and_infowindow']['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View mode the entity will be displayed in the Infowindow.'),
        '#options' => $view_mode_options,
        '#default_value' => $default_view_mode,
        '#states' => [
          'visible' => [
            ':input[name$="[settings][map_marker_and_infowindow][infowindow_field]"]' => [
              'value' => '#rendered_entity',
            ],
          ],
        ],
      ];
    }

    elseif ($this->entityType) {
      // Get the human readable labels for the entity view modes.
      $view_mode_options = [];
      foreach ($this->entityDisplay->getViewModes($this->entityType) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $elements['map_marker_and_infowindow']['view_mode'] = [
        '#fieldset' => 'map_marker_and_infowindow',
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View mode the entity will be displayed in the Infowindow.'),
        '#options' => $view_mode_options,
        '#default_value' => $default_view_mode,
        '#states' => [
          'visible' => [
            ':input[name="style_options[map_marker_and_infowindow][infowindow_field]"]' => [
              ['value' => '#rendered_entity'],
              ['value' => '#rendered_entity_ajax'],
            ],
          ],
        ],
      ];
    }

    if (isset($this->fieldDefinition)) {
      $elements['map_marker_and_infowindow']['tooltip_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Marker Tooltip'),
        '#description' => $this->t('Choose the option whose value will appear as Tooltip on hover the Marker.'),
        '#options' => $title_options,
        '#default_value' => $settings['map_marker_and_infowindow']['tooltip_field'],
      ];
      $elements['map_marker_and_infowindow']['force_open'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Open Infowindow on Load'),
        '#description' => $this->t('If checked the Infowindow will automatically open on page load.<br><b>Note:</b> in case of multivalue Geofield, the Infowindow will be opened (and the Map centered) on the first item.'),
        '#default_value' => !empty($settings['map_marker_and_infowindow']['force_open']) ? $settings['map_marker_and_infowindow']['force_open'] : 0,
        '#return_value' => 1,
      ];
    }
    else {
      $elements['map_marker_and_infowindow']['tooltip_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Marker Tooltip'),
        '#description' => $this->t('Choose the option whose value will appear as Tooltip on hover the Marker.'),
        '#options' => array_merge(['' => '- Any - No Tooltip'], $this->viewFields),
        '#default_value' => $settings['map_marker_and_infowindow']['tooltip_field'],
      ];
    }
  }

  /**
   * Set Map Additional Options Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapAdditionalOptionsElement(array $settings, array &$elements) {
    $elements['map_additional_options'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Map Additional Options'),
      '#description' => $this->t('<strong>These will override the above settings</strong><br>An object literal of additional map options, that comply with the Google Maps JavaScript API.<br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.<br>It is even possible to input Map Control Positions. For this use the numeric values of the google.maps.ControlPosition, otherwise the option will be passed as incomprehensible string to Google Maps API.'),
      '#default_value' => $settings['map_additional_options'],
      '#placeholder' => '{"disableDoubleClickZoom": "cooperative",
"gestureHandling": "none",
"streetViewControlOptions": {"position": 5}
}',
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];

    $elements['map_additional_libraries'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Map additional Libraries'),
      '#description' => $this->t('Select the additional @libraries_link that should be included with the Google Maps library request.', [
        '@libraries_link' => $this->link->generate('Google Map Libraries', Url::fromUri('https://developers.google.com/maps/documentation/javascript/drawinglayer', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => $settings['map_additional_libraries'],
      '#options' => [
        'places' => $this->t('Places'),
        'drawing' => $this->t('Drawing'),
        'geometry' => $this->t('Geometry'),
        'visualization' => $this->t('Visualization'),
      ],
    ];

  }

  /**
   * Set Map Geometries Options Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setGeometriesAdditionalOptionsElement(array $settings, array &$elements) {
    $token_replacement_disclaimer = $this->t('<b>Note: </b> Using <strong>Replacement Patterns</strong> it is possible to dynamically define the Path geometries options, based on the entity properties or fields values.');
    $elements['map_geometries_options'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Map Geometries Options'),
      '#description' => $this->t('Set here options that will be applied to the rendering of Map Geometries (Lines & Polylines, Polygons, Multipolygons, etc.).<br>Refer to the @polygons_documentation.<br>@token_replacement_disclaimer', [
        '@polygons_documentation' => $this->link->generate($this->t('Google Maps Polygons Documentation'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/reference/polygon#PolylineOptions', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
        '@token_replacement_disclaimer' => $token_replacement_disclaimer,
      ]),
      '#default_value' => $settings['map_geometries_options'],
      '#placeholder' => self::getDefaultSettings()['map_geometries_options'],
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];
  }

  /**
   * Set Overlapping Marker Spiderfier Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $default_settings
   *   The default settings array.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapOmsElement(array $settings, array $default_settings, array &$elements) {
    $elements['map_oms'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Overlapping Markers'),
      '#description' => $this->t('<b>Note: </b>To make this working in conjunction with the Markercluster Option (see below) a "maxZoom" property should be set in the Marker Cluster Additional Options.'),
      '#description_display' => 'before',
    ];
    $elements['map_oms']['map_oms_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Spiderfy overlapping markers'),
      '#description' => $this->t('Use the standard setup of the @overlapping_marker_spiderfier to manage Overlapping Markers located in the exact same position.', [
        '@overlapping_marker_spiderfier' => $this->link->generate(t('Overlapping Marker Spiderfier Library (for Google Maps)'), Url::fromUri('https://github.com/jawj/OverlappingMarkerSpiderfier#overlapping-marker-spiderfier-for-google-maps-api-v3', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => isset($settings['map_oms']['map_oms_control']) ? $settings['map_oms']['map_oms_control'] : $default_settings['map_oms']['map_oms_control'],
      '#return_value' => 1,
    ];
    $elements['map_oms']['map_oms_options'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Markers Spiderfy Options'),
      '#description' => $this->t('An object literal of Spiderfy options, that comply with the Overlapping Marker Spiderfier Library (see link above).<br>The syntax should respect the javascript object notation (json) format.<br>Always use double quotes (") both for the indexes and the string values.<br><b>Note: </b>This first three default options are the library ones suggested to save memory and CPU (in the simplest/standard implementation).'),
      '#default_value' => isset($settings['map_oms']['map_oms_options']) ? $settings['map_oms']['map_oms_options'] : $default_settings['map_oms']['map_oms_options'],
      '#placeholder' => '{"markersWontMove": "true", "markersWontHide": "true", "basicFormatEvents": "true", "nearbyDistance": 3}',
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];

    if (isset($this->fieldDefinition)) {
      $elements['map_oms']['map_oms_options']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_oms][map_oms_control]"]' => ['checked' => TRUE],
        ],
      ];
    }
    else {
      $elements['map_oms']['map_oms_options']['#states'] = [
        'visible' => [
          ':input[name="style_options[map_oms][map_oms_control]"]' => ['checked' => TRUE],
        ],
      ];
    }
  }

  /**
   * Set Custom Map Style Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setCustomStyleMapElement(array $settings, array &$elements) {
    $elements['custom_style_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Styled Map'),
    ];
    $elements['custom_style_map']['custom_style_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create a @custom_google_map_style_link.', [
        '@custom_google_map_style_link' => $this->link->generate($this->t('Custom Google Map Style'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/examples/maptype-styled-simple', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#description' => $this->t('This option allows to create a new map type, which the user can select from the map type control. The map type includes custom styles.'),
      '#default_value' => $settings['custom_style_map']['custom_style_control'],
      '#return_value' => 1,
    ];
    $elements['custom_style_map']['custom_style_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Map Style Name'),
      '#description' => $this->t('Input the Name of the Custom Map Style you want to create.'),
      '#default_value' => $settings['custom_style_map']['custom_style_name'],
      '#placeholder' => $this->t('My Custom Map Style'),
      '#element_validate' => [[get_class($this), 'customMapStyleValidate']],
    ];
    $elements['custom_style_map']['custom_style_options'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Custom Map Style Options'),
      '#description' => $this->t('An object literal of map style options, that comply with the Google Maps JavaScript API.<br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.<br>(As a useful reference consider using @snappy_maps).', [
        '@snappy_maps' => $this->link->generate($this->t('Snappy Maps'), Url::fromUri('https://snazzymaps.com', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => $settings['custom_style_map']['custom_style_options'],
      '#placeholder' => $this->customMapStylePlaceholder,
      '#element_validate' => [
        [get_class($this), 'jsonValidate'],
        [get_class($this), 'customMapStyleValidate'],
      ],
    ];
    $elements['custom_style_map']['custom_style_hint'] = [
      '#type' => 'container',
      'intro' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Hint: Use the following json text to disable the default Google Pois from the Map'),
      ],
      'json' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('<b>[{"featureType":"poi","stylers":[{"visibility":"off"}]}]</b>'),
      ],
    ];
    $elements['custom_style_map']['custom_style_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force the Custom Map Style as Default'),
      '#description' => $this->t('The Custom Map Style will be the Default starting one.'),
      '#default_value' => $settings['custom_style_map']['custom_style_default'],
      '#return_value' => 1,
    ];

    if (isset($this->fieldDefinition)) {
      $custom_style_map_control_selector = ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][custom_style_map][custom_style_control]"]';
    }
    else {
      $custom_style_map_control_selector = ':input[name="style_options[custom_style_map][custom_style_control]"]';
    }
    $elements['custom_style_map']['custom_style_name']['#states'] = [
      'visible' => [
        $custom_style_map_control_selector => ['checked' => TRUE],
      ],
      'required' => [
        $custom_style_map_control_selector => ['checked' => TRUE],
      ],
    ];
    $elements['custom_style_map']['custom_style_options']['#states'] = [
      'visible' => [
        $custom_style_map_control_selector => ['checked' => TRUE],
      ],
    ];
    $elements['custom_style_map']['custom_style_default']['#states'] = [
      'visible' => [
        $custom_style_map_control_selector => ['checked' => TRUE],
      ],
    ];
  }

  /**
   * Set Map Marker Cluster Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapMarkerclusterElement(array $settings, array &$elements) {
    $default_settings = $this::getDefaultSettings();

    $elements['map_markercluster'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Marker Clustering'),
    ];
    $elements['map_markercluster']['markup'] = [
      '#markup' => $this->t('Enable the functionality of the @markeclusterer_api_link.', [
        '@markeclusterer_api_link' => $this->link->generate($this->t('Marker Clusterer Google Maps JavaScript Library'), Url::fromUri('https://github.com/googlemaps/js-marker-clusterer', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];
    $elements['map_markercluster']['markercluster_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Marker Clustering'),
      '#default_value' => isset($settings['map_markercluster']['markercluster_control']) ? $settings['map_markercluster']['markercluster_control'] : $default_settings['map_markercluster']['markercluster_control'],
      '#return_value' => 1,
    ];
    $elements['map_markercluster']['markercluster_additional_options'] = [
      '#type' => 'textarea',
      '#rows' => 4,
      '#title' => $this->t('Marker Cluster Additional Options'),
      '#description' => $this->t('An object literal of additional marker cluster options, that comply with the Marker Clusterer Google Maps JavaScript Library.<br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
      '#default_value' => isset($settings['map_markercluster']['markercluster_additional_options']) ? $settings['map_markercluster']['markercluster_additional_options'] : $default_settings['map_markercluster']['markercluster_additional_options'],
      '#placeholder' => '{"maxZoom":12,"gridSize":50}',
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];

    $elements['map_markercluster']['markercluster_warning'] = [
      '#type' => 'container',
      'warning' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('WARNING:') . " ",
        '#attributes' => [
          'class' => ['geofield-map-warning'],
        ],
      ],
      'warning_text' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Markers Spiderfy is Active ! | A "maxZoom" property should be set in the Marker Cluster Options to output the Spiderfy effect.'),
      ],
    ];

    if (isset($this->fieldDefinition)) {
      $elements['map_markercluster']['markercluster_additional_options']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_markercluster][markercluster_control]"]' => ['checked' => TRUE],
        ],
      ];
      $elements['map_markercluster']['markercluster_warning']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_oms][map_oms_control]"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_markercluster][markercluster_control]"]' => ['checked' => FALSE],
        ],
      ];
    }
    else {
      $elements['map_markercluster']['markercluster_additional_options']['#states'] = [
        'visible' => [
          ':input[name="style_options[map_markercluster][markercluster_control]"]' => ['checked' => TRUE],
        ],
      ];
      $elements['map_markercluster']['markercluster_warning']['#states'] = [
        'visible' => [
          ':input[name="style_options[map_oms][map_oms_control]"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="style_options[map_markercluster][markercluster_control]"]' => ['checked' => FALSE],
        ],
      ];
    }
  }

  /**
   * Set Map Geocoder Control Element.
   *
   * @param array $element
   *   The Form element to alter.
   * @param array $settings
   *   The Form Settings.
   */
  protected function setGeocoderMapControl(array &$element, array $settings) {
    $geocoder_module_link = $this->link->generate('Geocoder Module', Url::fromUri('https://www.drupal.org/project/geocoder', ['attributes' => ['target' => 'blank']]));
    $element['map_geocoder'] = [
      '#type' => 'fieldset',
      '#title' => $this->getMapGeocoderTitle(),
    ];
    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    if ($this->moduleHandler->moduleExists('geocoder') && class_exists('\Drupal\geocoder\Controller\GeocoderApiEnpoints')) {
      $default_settings = $this::getDefaultSettings();

      $map_geocoder_control = isset($settings['map_geocoder']) ? $settings['map_geocoder']['control'] : FALSE;

      $element['map_geocoder']['access_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('<strong>Note: </strong>This will show to users with permissions to <u>Access Geocoder Api Url Enpoints.</u>'),
        '#attributes' => [
          'style' => 'color: red;',
        ],
      ];
      $element['map_geocoder']['control'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @search_address_geocoder', [
          '@search_address_geocoder' => $element['map_geocoder']['#title'],
        ]),
        '#description' => $this->t('This will add a Geocoder control element to the Geofield Map'),
        '#default_value' => $map_geocoder_control ?: $default_settings['map_geocoder']['control'],
      ];

      $element['map_geocoder']['settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Geocoder Settings'),
      ];

      $element['map_geocoder']['settings']['position'] = [
        '#type' => 'select',
        '#title' => $this->t('Position'),
        '#options' => $this->controlPositionsOptions,
        '#default_value' => isset($settings['map_geocoder']['settings']['position']) ? $settings['map_geocoder']['settings']['position'] : $default_settings['map_geocoder']['settings']['position'],
      ];

      $element['map_geocoder']['settings']['input_size'] = [
        '#title' => $this->t('Input Size'),
        '#type' => 'number',
        '#min' => 10,
        '#max' => 100,
        '#default_value' => isset($settings['map_geocoder']['settings']['input_size']) ? $settings['map_geocoder']['settings']['input_size'] : $default_settings['map_geocoder']['settings']['input_size'],
        '#description' => $this->t('The characters size/length of the Geocoder Input element.'),
      ];

      $providers_settings = isset($settings['map_geocoder']['settings']['providers']) ? $settings['map_geocoder']['settings']['providers'] : [];

      // Get the enabled/selected providers.
      $enabled_providers = [];
      foreach ($providers_settings as $plugin_id => $plugin) {
        if (!empty($plugin['checked'])) {
          $enabled_providers[] = $plugin_id;
        }
      }

      // Generates the Draggable Table of Selectable Geocoder Providers.
      /** @var \Drupal\geocoder\ProviderPluginManager  $geocoder_provider */
      $geocoder_provider = \Drupal::service('plugin.manager.geocoder.provider');
      $element['map_geocoder']['settings']['providers'] = $geocoder_provider->providersPluginsTableList($enabled_providers);

      // Set a validation for the providers selection.
      $element['map_geocoder']['settings']['providers']['#element_validate'] = [[get_class($this), 'validateGeocoderProviders']];

      $element['map_geocoder']['settings']['min_terms'] = [
        '#type' => 'number',
        '#default_value' => isset($settings['map_geocoder']['settings']['min_terms']) ? $settings['map_geocoder']['settings']['min_terms'] : $default_settings['map_geocoder']['settings']['min_terms'],
        '#title' => $this->t('The (minimum) number of terms for the Geocoder to start processing.'),
        '#description' => $this->t('Valid values ​​for the widget are between 2 and 10. A too low value (<= 3) will affect the application Geocode Quota usage.<br>Try to increase this value if you are experiencing Quota usage matters.'),
        '#min' => 2,
        '#max' => 10,
        '#size' => 3,
      ];

      $element['map_geocoder']['settings']['delay'] = [
        '#type' => 'number',
        '#default_value' => isset($settings['map_geocoder']['settings']['delay']) ? $settings['map_geocoder']['settings']['delay'] : $default_settings['map_geocoder']['settings']['delay'],
        '#title' => $this->t('The delay (in milliseconds) between pressing a key in the Address Input field and starting the Geocoder search.'),
        '#description' => $this->t('Valid values ​​for the widget are multiples of 100, between 300 and 3000. A too low value (<= 300) will affect / increase the application Geocode Quota usage.<br>Try to increase this value if you are experiencing Quota usage matters.'),
        '#min' => 300,
        '#max' => 3000,
        '#step' => 100,
        '#size' => 4,
      ];

      $element['map_geocoder']['settings']['zoom'] = [
        '#title' => $this->t('Zoom to Focus'),
        '#type' => 'number',
        '#min' => 1,
        '#max' => 22,
        '#default_value' => isset($settings['map_geocoder']['settings']['zoom']) ? $settings['map_geocoder']['settings']['zoom'] : $default_settings['map_geocoder']['settings']['zoom'],
        '#description' => $this->t('Zoom level to Focus on the Map upon the Geocoder Address selection.'),
      ];

      $element['map_geocoder']['settings']['infowindow'] = [
        '#title' => $this->t('Open infowindow on Geocode Focus'),
        '#type' => 'checkbox',
        '#default_value' => isset($settings['map_geocoder']['settings']['infowindow']) ? $settings['map_geocoder']['settings']['infowindow'] : $default_settings['map_geocoder']['settings']['infowindow'],
        '#description' => $this->t('Check this to open an Infowindow on the Map (with the found Address) upon the Geocode Focus.'),
      ];

      $element['map_geocoder']['settings']['options'] = [
        '#type' => 'textarea',
        '#rows' => 4,
        '#title' => $this->t('Geocoder Control Specific Options'),
        '#description' => $this->t('This settings would override general Geocoder Providers options. (<u>Note: This would work only for Geocoder 2.x branch/version.</u>)<br>An object literal of specific Geocoder options.The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
        '#default_value' => isset($settings['map_geocoder']['settings']['options']) ? $settings['map_geocoder']['settings']['options'] : $default_settings['map_geocoder']['settings']['options'],
        '#placeholder' => '{"googlemaps":{"locale": "it", "region": "it"}, "nominatim":{"locale": "it"}}',
        '#element_validate' => [[get_class($this), 'jsonValidate']],
      ];
      if (isset($this->fieldDefinition)) {
        $element['map_geocoder']['settings']['#states'] = [
          'visible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_geocoder][control]"]' => ['checked' => TRUE],
          ],
        ];
      }
      else {
        $element['map_geocoder']['settings']['#states'] = [
          'visible' => [
            ':input[name="style_options[map_geocoder][control]"]' => ['checked' => TRUE],
          ],
        ];
      }
    }
    else {
      $element['map_geocoder']['enable_warning'] = [
        '#markup' => $this->t('<strong>Note: </strong>it is possible to enable the <u>Search Address input element on the Geofield Map</u> throughout the @geocoder_module_link integration (version higher than 8.x-2.3 and 8.x-3.0-alpha2).', [
          '@geocoder_module_link' => $geocoder_module_link,
        ]),
      ];
    }
  }

  /**
   * Set Map Lazy Load Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  protected function setMapLazyLoad(array $settings, array &$elements) {
    $elements['map_lazy_load'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Lazy Loading'),
    ];

    $elements['map_lazy_load']['lazy_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy load map'),
      '#description' => $this->t("If checked, the map will be loaded when it enters the user's viewport. This can be useful to reduce unnecessary load time or API calls."),
      '#default_value' => !empty($settings['map_lazy_load']['lazy_load']) ? $settings['map_lazy_load']['lazy_load'] : 0,
      '#return_value' => 1,
    ];
  }

  /**
   * Validates the Geocoder Providers element.
   *
   * @param array $element
   *   The form element to build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateGeocoderProviders(array $element, FormStateInterface &$form_state) {
    $form_state_input = $form_state->getUserInput();
    if (isset($form_state_input['style_options'])) {
      $geocoder_control = $form_state_input['style_options']['map_geocoder']['control'];
    }
    if (isset($form_state_input['fields'])) {
      $geocoder_control = $form_state_input['fields'][$element['#array_parents'][1]]['settings_edit_form']['settings']['map_geocoder']['control'];
    }
    if (isset($geocoder_control) && $geocoder_control) {
      $providers = is_array($element['#value']) ? array_filter($element['#value'], function ($value) {
        return isset($value['checked']) && TRUE == $value['checked'];
      }) : [];

      if (empty($providers)) {
        $form_state->setError($element, t('The "Geocoder Settings" needs at least one geocoder plugin selected.'));
      }
    }
  }

}
