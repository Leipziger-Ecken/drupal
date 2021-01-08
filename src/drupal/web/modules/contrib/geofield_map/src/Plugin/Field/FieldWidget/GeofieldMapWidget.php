<?php

namespace Drupal\geofield_map\Plugin\Field\FieldWidget;

use Drupal\geofield_map\GeofieldMapFieldTrait;
use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldLatLonWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\geofield_map\LeafletTileLayerPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin implementation of the 'geofield_map' widget.
 *
 * @FieldWidget(
 *   id = "geofield_map",
 *   label = @Translation("Geofield Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldMapWidget extends GeofieldLatLonWidget implements ContainerFactoryPluginInterface {

  use GeofieldMapFieldTrait;
  use GeofieldMapFormElementsValidationTrait;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The WKT format Generator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The LeafletTileLayer Manager service.
   *
   * @var \Drupal\geofield_map\LeafletTileLayerPluginManager
   */
  protected $leafletTileManager;

  /**
   * The Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Lat Lon widget components.
   *
   * @var array
   */
  public $components = ['lon', 'lat'];

  /**
   * Leaflet Map Tile Layers.
   *
   * Free Leaflet Tile Layers from here:
   * http://leaflet-extras.github.io/leaflet-providers/preview/index.html .
   *
   * @var array
   */
  protected $leafletTileLayers;

  /**
   * Leaflet Map Tile Layers Options.
   *
   * @var array
   */
  protected $leafletTileLayersOptions;

  /**
   * GeofieldMapWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface|null $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\geofield\WktGeneratorInterface|null $wkt_generator
   *   The WKT format Generator service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\geofield_map\LeafletTileLayerPluginManager $leaflet_tile_manager
   *   The LeafletTileLayer Manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The Current User.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GeoPHPInterface $geophp_wrapper,
    WktGeneratorInterface $wkt_generator,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    RendererInterface $renderer,
    EntityFieldManagerInterface $entity_field_manager,
    LinkGeneratorInterface $link_generator,
    LeafletTileLayerPluginManager $leaflet_tile_manager,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $geophp_wrapper, $wkt_generator);
    $this->config = $config_factory;
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
    $this->link = $link_generator;
    $this->wktGenerator = $wkt_generator;
    $this->leafletTileManager = $leaflet_tile_manager;
    $this->leafletTileLayers = $this->leafletTileManager->getLeafletTileLayers();
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('geofield.geophp'),
      $container->get('geofield.wkt_generator'),
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('link_generator'),
      $container->get('plugin.manager.leaflet_tile_layer_plugin'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_value' => [
        'lat' => '0',
        'lon' => '0',
      ],
      'map_library' => 'leaflet',
      'map_google_api_key' => '',
      'map_google_places' => [
        'places_control' => FALSE,
        'places_additional_options' => '',
      ],
      'map_geocoder' => [
        'control' => 0,
        'settings' => [
          'providers' => [],
          'min_terms' => 4,
          'delay' => 800,
          'options' => '',
        ],
      ],
      'map_dimensions' => [
        'width' => '100%',
        'height' => '450px',
      ],
      'map_type_google' => 'roadmap',
      'map_type_leaflet' => 'OpenStreetMap_Mapnik',
      'map_type_selector' => TRUE,
      'zoom_level' => 5,
      'zoom' => [
        'start' => 6,
        'focus' => 12,
        'min' => 0,
        'max' => 22,
      ],
      'click_to_find_marker' => FALSE,
      'click_to_place_marker' => FALSE,
      'hide_coordinates' => FALSE,
      'geoaddress_field' => [
        'field' => '0',
        'hidden' => FALSE,
        'disabled' => TRUE,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $default_settings = self::defaultSettings();
    $elements = [];
    $gmap_settings_page_link = Url::fromRoute('geofield_map.settings', [], [
      'query' => [
        'destination' => Url::fromRoute('<current>')
          ->toString(),
      ],
    ]);

    // Attach Geofield Map Library.
    $elements['#attached']['library'] = [
      'geofield_map/geofield_map_general',
      'geofield_map/geofield_map_widget',
    ];

    $elements['#tree'] = TRUE;

    $elements['default_value'] = [
      'lat' => [
        '#type' => 'value',
        '#value' => $this->getSetting('default_value')['lat'],
      ],
      'lon' => [
        '#type' => 'value',
        '#value' => $this->getSetting('default_value')['lon'],
      ],
    ];

    $geocoder_module_link = $this->link->generate('Geocoder Module', Url::fromUri('https://www.drupal.org/project/geocoder', ['attributes' => ['target' => 'blank']]));

    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    $this->setGeocoderMapControl($elements, $this->getSettings());

    // Alter and tune the original 'map_geocoder' sub-settings.
    unset($elements['map_geocoder']['access_warning']);
    unset($elements['map_geocoder']['settings']['position']);
    unset($elements['map_geocoder']['settings']['input_size']);
    unset($elements['map_geocoder']['settings']['infowindow']);
    unset($elements['map_geocoder']['settings']['zoom']);

    $elements['map_geocoder']['control']['#description'] = $this->t('This enables Geocoding capabilities throughout the @geocoder_module_link providers (and overtakes the Geocode & ReverseGeocode functionalities provided by the Google Maps Geocoder).', [
      '@geocoder_module_link'  => $geocoder_module_link,
    ]);

    // Set Google Api Key Element.
    $elements['map_google_api_key'] = $this->setMapGoogleApiKeyElement();

    $elements['map_google_places'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Places'),
      '#description' => $this->t('Note: this works only with Gmap Api Key set in the @geofield_map_settings_link page. Cannot work with "@search_address_geocoder_option_title" enabled', [
        '@geofield_map_settings_link' => $this->link->generate('Geofield Map Settings', $gmap_settings_page_link),
        '@search_address_geocoder_option_title' => $this->getMapGeocoderTitle(),
      ]),
      '#states' => [
        'invisible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][map_geocoder][control]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['map_google_places']['places_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Address Geocoding via the @google_places_link.', [
        '@google_places_link' => $this->link->generate($this->t('Google Maps Places Autocomplete Service'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => $this->getSetting('map_google_places')['places_control'],
      '#return_value' => 1,
    ];
    $elements['map_google_places']['places_additional_options'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Google Maps Places Autocomplete Service Additional Options'),
      '#description' => $this->t('An object literal of additional options, that comply with the @autocomplete_class.<br><b>The placeholder values are the default ones used by the widget.</b><br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.', [
        "@autocomplete_class" => $this->link->generate($this->t('google.maps.places.Autocomplete class'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/reference/3/#Autocomplete', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => $this->getSetting('map_google_places')['places_additional_options'],
      '#placeholder' => '{"fields": ["place_id"], "strictBounds": "false"}',
      '#element_validate' => [[get_class($this), 'jsonValidate']],
      '#states' => [
        'visible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][map_google_places][places_control]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['map_library'] = [
      '#type' => 'select',
      '#title' => $this->t('Map Library'),
      '#default_value' => $this->getSetting('map_library'),
      '#options' => [
        'gmap' => $this->t('Google Maps'),
        'leaflet' => $this->t('Leaflet js'),
      ],
    ];

    // Alter settings elements in case of Google Maps API Key missing.
    if (empty($this->getGmapApiKey())) {
      $elements['map_google_places']['#description'] = $this->t("<span class='geofield-map-warning'>Gmap Api Key missing - Google Places library not available.</span>");
      $elements['map_google_places']['places_control']['#disabled'] = TRUE;
      $elements['map_google_places']['places_control']['#default_value'] = FALSE;
      $elements['map_library']['#default_value'] = 'leaflet';
      $elements['map_library']['#description'] = $this->t("<span class='geofield-map-warning'>Gmap Api Key missing - Google Maps Library not available.</span>");
      $elements['map_library']['#options'] = ['leaflet' => $this->t('Leaflet js')];
    }

    $elements['map_type_google'] = [
      '#type' => 'select',
      '#title' => $this->t('Map type'),
      '#default_value' => $this->getSetting('map_type_google'),
      '#options' => $this->gMapTypesOptions,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_library]"]' => ['value' => 'gmap'],
        ],
      ],
    ];

    $elements['map_type_leaflet'] = [
      '#type' => 'select',
      '#title' => $this->t('Map type'),
      '#default_value' => $this->getSetting('map_type_leaflet'),
      '#options' => $this->leafletTileManager->getLeafletTilesLayersOptions(),
      '#description' => $this->currentUser->hasPermission('configure geofield_map') ? $this->t('Choose one among all the Leaflet Tiles Plugins defined for the Geofield Map module (@see LeafletTileLayerPlugin).<br>You can add your one into your custom module as a new LeafletTileLayer Plugin. (Free Leaflet Tile Layers definitions are available from @free_leaflet_tiles_link).', [
        '@free_leaflet_tiles_link' => $this->link->generate($this->t('leaflet-extras/leaflet-providers'), Url::fromUri('http://leaflet-extras.github.io/leaflet-providers/preview/index.html', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]) : '',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_library]"]' => ['value' => 'leaflet'],
        ],
      ],
    ];

    $elements['map_type_selector'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a Map type Selector on the Map'),
      '#description' => $this->t('If checked, the user will be able to change Map Type throughout the selector.'),
      '#default_value' => $this->getSetting('map_type_selector'),
    ];

    $elements['map_dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    ];
    $elements['map_dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $this->getSetting('map_dimensions')['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
    $elements['map_dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $this->getSetting('map_dimensions')['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];

    $elements['zoom'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Zoom Settings'),
    ];
    $elements['zoom']['start'] = [
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Start Zoom level'),
      '#description' => $this->t('The initial Zoom level for an empty Geofield.'),
      '#default_value' => $this->getSetting('zoom')['start'],
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    ];
    $elements['zoom']['focus'] = [
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Focus Zoom level'),
      '#description' => $this->t('The Zoom level for an assigned Geofield or for Geocoding operations results.'),
      '#default_value' => $this->getSetting('zoom')['focus'],
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    ];
    $elements['zoom']['min'] = [
      '#type' => 'number',
      '#min' => $default_settings['zoom']['min'],
      '#max' => $default_settings['zoom']['max'],
      '#title' => $this->t('Min Zoom level'),
      '#description' => $this->t('The Minimum Zoom level for the Map.'),
      '#default_value' => $this->getSetting('zoom')['min'],
    ];
    $elements['zoom']['max'] = [
      '#type' => 'number',
      '#min' => $default_settings['zoom']['min'],
      '#max' => $default_settings['zoom']['max'],
      '#title' => $this->t('Max Zoom level'),
      '#description' => $this->t('The Maximum Zoom level for the Map.'),
      '#default_value' => $this->getSetting('zoom')['max'],
      '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
    ];

    $elements['click_to_find_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click to Find marker'),
      '#description' => $this->t('Provides a button to recenter the map on the marker location.'),
      '#default_value' => $this->getSetting('click_to_find_marker'),
    ];

    $elements['click_to_place_marker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click to place marker'),
      '#description' => $this->t('Provides a button to place the marker in the center location.'),
      '#default_value' => $this->getSetting('click_to_place_marker'),
    ];

    $elements['hide_coordinates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Lat/Lon Coordinates Input'),
      '#description' => $this->t('Option to visually hide the coordinates input elements from the widget form.'),
      '#default_value' => $this->getSetting('hide_coordinates'),
    ];

    $fields_list = array_merge_recursive(
      $this->entityFieldManager->getFieldMapByFieldType('string_long'),
      $this->entityFieldManager->getFieldMapByFieldType('string')
    );

    $string_fields_options = [
      '0' => $this->t('- Any -'),
    ];

    // Filter out the not acceptable values from the options.
    foreach ($fields_list[$form['#entity_type']] as $k => $field) {
      if (in_array(
          $form['#bundle'], $field['bundles']) &&
        !in_array($k, [
          'revision_log',
          'behavior_settings',
          'parent_id',
          'parent_type',
          'parent_field_name',
        ])) {
        $string_fields_options[$k] = $k;
      }
    }

    $elements['geoaddress_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geoaddress-ed Field'),
      '#description' => $this->t('If a not null Google Maps API Key is set, it is possible to choose the Entity Title, or a "string" type field (among the content type ones), to sync and populate with the Search / Reverse Geocoded Address.'),
      '#states' => [
        'invisible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][map_google_api_key]"]' => ['value' => ''],
        ],
      ],
    ];
    $elements['geoaddress_field']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose an existing field where to store the Searched / Reverse Geocoded Address'),
      '#description' => $this->t('Choose among the title and the simple string fields (un-formatted text fields) of this bundle/entity type, if available.'),
      '#options' => $string_fields_options,
      '#default_value' => $this->getSetting('geoaddress_field')['field'],
    ];
    $elements['geoaddress_field']['hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Hide</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the selected Geoaddress Field will be Hidden to the user in the edit form, </br>and totally managed by the Geofield Reverse Geocode'),
      '#default_value' => $this->getSetting('geoaddress_field')['hidden'],
      '#states' => [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][geoaddress_field][field]"]' => ['value' => 'title']],
          'or',
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][geoaddress_field][field]"]' => ['value' => '0']],
        ],
      ],
    ];
    $elements['geoaddress_field']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Disable</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the selected Geoaddress Field will be Disabled to the user in the edit form, </br>and totally managed by the Geofield Reverse Geocode'),
      '#default_value' => $this->getSetting('geoaddress_field')['disabled'],
      '#states' => [
        'invisible' => [
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][geoaddress_field][hidden]"]' => ['checked' => TRUE]],
          'or',
          [':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][geoaddress_field][field]"]' => ['value' => '0']],
        ],
      ],
    ];

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $gmap_api_key = $this->getGmapApiKey();

    $map_library = [
      '#markup' => $this->t('Map Library: @state', ['@state' => 'gmap' == $this->getSetting('map_library') ? 'Google Maps' : 'Leaflet Js']),
    ];

    $map_type = [
      '#markup' => $this->t('Map Type: @state', ['@state' => 'leaflet' == $this->getSetting('map_library') ? $this->getSetting('map_type_leaflet') : $this->getSetting('map_type_google')]),
    ];

    $map_google_places = [
      '#markup' => ' - ' . $this->t('Google Places Autocomplete Service: @state', ['@state' => $this->getSetting('map_google_places')['places_control'] && !empty($gmap_api_key) ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $map_type_selector = [
      '#markup' => $this->t('Map Type Selector: @state', ['@state' => $this->getSetting('map_type_selector') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $map_dimensions = [
      '#markup' => $this->t('Map Dimensions -'),
    ];

    $map_dimensions['#markup'] .= ' ' . $this->t('Width: @state;', ['@state' => $this->getSetting('map_dimensions')['width']]);
    $map_dimensions['#markup'] .= ' ' . $this->t('Height: @state;', ['@state' => $this->getSetting('map_dimensions')['height']]);

    $map_zoom_levels = [
      '#markup' => $this->t('Zoom Levels -'),
    ];

    $map_zoom_levels['#markup'] .= ' ' . $this->t('Start: @state;', ['@state' => $this->getSetting('zoom')['start']]);
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Focus: @state;', ['@state' => $this->getSetting('zoom')['focus']]);
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Min: @state;', ['@state' => $this->getSetting('zoom')['min']]);
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Max: @state;', ['@state' => $this->getSetting('zoom')['max']]);

    $html5 = [
      '#markup' => $this->t('HTML5 Geolocation button: @state', ['@state' => $this->getSetting('html5_geolocation') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $map_center = [
      '#markup' => $this->t('Click to find marker: @state', ['@state' => $this->getSetting('click_to_find_marker') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $marker_center = [
      '#markup' => $this->t('Click to place marker: @state', ['@state' => $this->getSetting('click_to_place_marker') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $hide_coordinates = [
      '#markup' => $this->t('Lat/Lon coordinates hidden: @state', ['@state' => $this->getSetting('hide_coordinates') ? $this->t('enabled') : $this->t('disabled')]),
    ];

    $geoaddress_field_field = [
      '#markup' => $this->t('Geoaddress Field: @state', ['@state' => ('0' != $this->getSetting('geoaddress_field')['field']) ? $this->getSetting('geoaddress_field')['field'] : $this->t('- any -')]),
    ];

    $geoaddress_field_hidden = [
      '#markup' => ('0' != $this->getSetting('geoaddress_field')['field']) ? $this->t('Geoaddress Field Hidden: @state', ['@state' => $this->getSetting('geoaddress_field')['hidden']]) : '',
    ];

    $geoaddress_field_disabled = [
      '#markup' => ('0' != $this->getSetting('geoaddress_field')['field']) ? $this->t('Geoaddress Field Disabled: @state', ['@state' => $this->getSetting('geoaddress_field')['disabled']]) : '',
    ];

    $gmap_geocoder_enabled = $this->moduleHandler->moduleExists('geocoder') && $this->getSetting('map_geocoder')['control'];

    $summary = [
      'map_google_api_key' => $this->setMapGoogleApiKeyElement(),
      'map_geocoder' => [
        '#type' => 'container',
        'title'  => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Search Address Functionalities based on:'),
        ],
        'type'  => $gmap_geocoder_enabled ? [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => ' - ' . $this->t('Geocoder Module Providers'),
        ] : [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => ' - ' . $this->t('Geofield Map GMaps API Key Geocoder') . (empty($gmap_api_key) ? ' ' . $this->t("<span class='geofield-map-warning'> Not available</span>") : ''),
        ],
        'map_google_places' => !$this->getSetting('map_geocoder')['control'] ? $map_google_places : ['#markup' => ''],
      ],
      'map_library' => $map_library,
      'map_type' => $map_type,
      'map_type_selector' => $map_type_selector,
      'map_dimensions' => $map_dimensions,
      'map_zoom_levels' => $map_zoom_levels,
      'html5' => $html5,
      'map_center' => $map_center,
      'marker_center' => $marker_center,
      'hide_coordinates' => $hide_coordinates,
      'field' => $geoaddress_field_field,
      'hidden' => $geoaddress_field_hidden,
      'disabled' => $geoaddress_field_disabled,
    ];

    // Attach Geofield Map Library.
    $summary['library'] = [
      '#attached' => [
        'library' => [
          'geofield_map/geofield_map_general',
        ],
      ],
    ];

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /* @var \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem $geofield_item */
    $geofield_item = $items->getValue()[$delta];
    if (empty($geofield_item) || $geofield_item['geo_type'] == 'Point') {

      $gmap_api_key = $this->getGmapApiKey();

      $latlon_value = [];

      foreach ($this->components as $component) {
        $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : $this->getSetting('default_value')[$component];
      }

      /* @var \Drupal\geofield_map\Services\GeocoderService $geofield_map_geocoder_service */
      $geofield_map_geocoder_service = \Drupal::service('geofield_map.geocoder');

      $element += [
        '#type' => 'geofield_map',
        '#gmap_api_key' => $gmap_api_key,
        '#gmap_places' => (int) $this->getSetting('map_google_places')['places_control'],
        '#gmap_places_options' => $this->getSetting('map_google_places')['places_additional_options'],
        '#gmap_geocoder' => (int) $this->getSetting('map_geocoder')['control'],
        '#gmap_geocoder_settings' => $geofield_map_geocoder_service->getJsGeocoderSettings($this->getSetting('map_geocoder')['settings']),
        '#default_value' => $latlon_value,
        '#geolocation' => $this->getSetting('html5_geolocation'),
        '#geofield_map_geolocation_override' => $this->getSetting('html5_geolocation'),
        '#map_library' => $this->getSetting('map_library'),
        '#map_type' => 'leaflet' === $this->getSetting('map_library') ? $this->getSetting('map_type_leaflet') : $this->getSetting('map_type_google'),
        '#map_type_selector' => $this->getSetting('map_type_selector'),
        '#map_types_google' => $this->gMapTypesOptions,
        '#map_types_leaflet' => $this->leafletTileLayers,
        '#map_dimensions' => $this->getSetting('map_dimensions'),
        '#zoom' => $this->getSetting('zoom'),
        '#click_to_find_marker' => $this->getSetting('click_to_find_marker'),
        '#click_to_place_marker' => $this->getSetting('click_to_place_marker'),
        '#hide_coordinates' => $this->getSetting('hide_coordinates'),
        '#geoaddress_field' => $this->getSetting('geoaddress_field'),
        '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
      ];
    }
    else {
      $element += [
        '#prefix' => '<div class="geofield-map-warning">' . t('This Geofield Map cannot be applied because Polylines and Polygons are not supported at the moment') . '</div>',
        '#type' => 'textarea',
        '#default_value' => $items[$delta]->value ?: NULL,
      ];
    }

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if (is_array($value['value'])) {
        foreach ($this->components as $component) {
          if (empty($value['value'][$component]) || !is_numeric($value['value'][$component])) {
            $values[$delta]['value'] = '';
            continue 2;
          }
        }
        $components = $value['value'];
        $values[$delta]['value'] = $this->wktGenerator->wktBuildPoint([
          $components['lon'],
          $components['lat'],
        ]);
      }
      /* @var \Geometry $geom */
      elseif ($geom = $this->geoPhpWrapper->load($value['value'])) {
        $values[$delta]['value'] = $geom->out('wkt');
      }
    }
    return $values;
  }

}
