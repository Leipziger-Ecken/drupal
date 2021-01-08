<?php

namespace Drupal\leaflet\Plugin\Field\FieldWidget;

use Drupal\leaflet\LeafletSettingsElementsTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldDefaultWidget;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\leaflet\LeafletService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the "leaflet_widget" widget.
 *
 * @FieldWidget(
 *   id = "leaflet_widget_default",
 *   label = @Translation("Leaflet Map (default)"),
 *   description = @Translation("Provides a Leaflet Widget with Geoman Js Library."),
 *   field_types = {
 *     "geofield",
 *   },
 * )
 */
class LeafletDefaultWidget extends GeofieldDefaultWidget {

  use LeafletSettingsElementsTrait;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The token service.
   *
   * @var \Drupal\core\Utility\Token
   */
  protected $token;

  /**
   * Get maps available for use with Leaflet.
   */
  protected static function getLeafletMaps() {
    $options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $options[$key] = $map['label'];
    }
    return $options;
  }

  /**
   * LeafletWidget constructor.
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
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\geofield\WktGeneratorInterface $wkt_generator
   *   The WKT format Generator service.
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GeoPHPInterface $geophp_wrapper,
    WktGeneratorInterface $wkt_generator,
    LeafletService $leaflet_service,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    Token $token
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $geophp_wrapper,
      $wkt_generator
    );
    $this->leafletService = $leaflet_service;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
    $this->token = $token;
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
      $container->get('leaflet.service'),
      $container->get('module_handler'),
      $container->get('link_generator'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $base_layers = self::getLeafletMaps();
    return [
      'map' => [
        'leaflet_map' => array_shift($base_layers),
        'height' => 400,
        'auto_center' => TRUE,
        'map_position' => self::getDefaultSettings()['map_position'],
        'locate' => TRUE,
        'scroll_zoom_enabled' => TRUE,
        'fullscreen_control' => TRUE,
      ],
      'input' => [
        'show' => TRUE,
        'readonly' => FALSE,
      ],
      'toolbar' => [
        'position' => 'topright',
        'marker' => 'defaultMarker',
        'drawPolyline' => TRUE,
        'drawRectangle' => TRUE,
        'drawPolygon' => TRUE,
        'drawCircle' => FALSE,
        'editMode' => TRUE,
        'dragMode' => TRUE,
        'cutPolygon' => FALSE,
        'removalMode' => TRUE,
      ],
      'reset_map' => self::getDefaultSettings()['reset_map'],
      'path' => self::getDefaultSettings()['path'],
      'geocoder' => self::getDefaultSettings()['geocoder'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $map_settings = $this->getSetting('map');
    $default_settings = self::defaultSettings();
    $form['map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Settings'),
    ];
    $form['map']['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => ['' => $this->t('-- Empty --')] + $this->getLeafletMaps(),
      '#default_value' => $map_settings['leaflet_map'] ?? $default_settings['map']['leaflet_map'],
      '#required' => TRUE,
    ];
    $form['map']['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $map_settings['height'] ?? $default_settings['map']['height'],
    ];
    $form['map']['auto_center'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically center map on existing features'),
      '#description' => t("This option overrides the widget's default center."),
      '#default_value' => $map_settings['auto_center'] ?? $default_settings['map']['auto_center'],
    ];

    // Generate the Leaflet Map Position Form Element.
    $map_position_options = $map_settings['map_position'] ?? $default_settings['map']['map_position'];
    $form['map']['map_position'] = $this->generateMapPositionElement($map_position_options);

    $form['map']['locate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically locate user current position'),
      '#description' => t("This option centers the map to the user position."),
      '#default_value' => $map_settings['locate'] ?? $default_settings['map']['locate'],
    ];
    $form['map']['scroll_zoom_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Scroll Wheel Zoom on click'),
      '#description' => t("This option enables zooming by mousewheel as soon as the user clicked on the map."),
      '#default_value' => $map_settings['scroll_zoom_enabled'] ?? $default_settings['map']['scroll_zoom_enabled'],
    ];

    $form['map']['fullscreen_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fullscreen Control'),
      '#description' => $this->t('Enable the Fullscreen View of the Map.'),
      '#default_value' => $map_settings['fullscreen_control'] ?? $default_settings['map']['fullscreen_control'],
      '#return_value' => 1,
    ];

    $input_settings = $this->getSetting('input');
    $form['input'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geofield Settings'),
    ];
    $form['input']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show geofield input element'),
      '#default_value' => $input_settings['show'] ?? $default_settings['input']['show'],
    ];
    $form['input']['readonly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make geofield input element read-only'),
      '#default_value' => $input_settings['readonly'] ?? $default_settings['input']['readonly'],
      '#states' => [
        'invisible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][input][show]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $toolbar_settings = $this->getSetting('toolbar');

    $form['toolbar'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Leaflet PM Settings'),
    ];

    $form['toolbar']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Toolbar position.'),
      '#options' => [
        'topleft' => 'topleft',
        'topright' => 'topright',
        'bottomleft' => 'bottomleft',
        'bottomright' => 'bottomright',
      ],
      '#default_value' => $toolbar_settings['position'] ?? $default_settings['toolbar']['position'],
    ];

    $form['toolbar']['marker'] = [
      '#type' => 'radios',
      '#title' => $this->t('Marker button.'),
      '#options' => [
        'none' => $this->t('None'),
        'defaultMarker' => $this->t('Default marker'),
        'circleMarker' => $this->t('Circle marker'),
      ],
      '#description' => $this->t('Use <b>Default marker</b> for default Point Marker. In case of <b>Circle marker</b> size can be changed by setting the <em>radius</em> property in <strong>Path Geometries Options</strong> below'),
      '#default_value' => $toolbar_settings['marker'] ?? $default_settings['toolbar']['marker'],
    ];
    $form['toolbar']['drawPolyline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw polyline.'),
      '#default_value' => $toolbar_settings['drawPolyline'] ?? $default_settings['toolbar']['drawPolyline'],
    ];

    $form['toolbar']['drawRectangle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw rectangle.'),
      '#default_value' => $toolbar_settings['drawRectangle'] ?? $default_settings['toolbar']['drawRectangle'],
    ];

    $form['toolbar']['drawPolygon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw polygon.'),
      '#default_value' => $toolbar_settings['drawPolygon'] ?? $default_settings['toolbar']['drawPolygon'],
    ];

    $form['toolbar']['drawCircle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to draw circle. (unsupported by GeoJSON'),
      '#default_value' => $toolbar_settings['drawCircle'] ?? $default_settings['toolbar']['drawCircle'],
      '#disabled' => TRUE,
    ];

//    $form['toolbar']['drawCircleMarker'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Adds button to draw circle marker. (unsupported by GeoJSON'),
//      '#default_value' => $toolbar_settings['drawCircleMarker'] ?? $default_settings['toolbar']['drawCircleMarker'],
//      '#disabled' => TRUE,
//    ];

    $form['toolbar']['editMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to toggle edit mode for all layers.'),
      '#default_value' => $toolbar_settings['editMode'] ?? $default_settings['toolbar']['editMode'],
    ];

    $form['toolbar']['dragMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to toggle drag mode for all layers.'),
      '#default_value' => $toolbar_settings['dragMode'] ?? $default_settings['toolbar']['dragMode'],
    ];

    $form['toolbar']['cutPolygon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to cut hole in polygon.'),
      '#default_value' => $toolbar_settings['cutPolygon'] ?? $default_settings['toolbar']['cutPolygon'],
    ];

    $form['toolbar']['removalMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Adds button to remove layers.'),
      '#default_value' => $toolbar_settings['removalMode'] ?? $default_settings['toolbar']['removalMode'],
    ];

    // Generate the Leaflet Map Reset Control.
    $this->setResetMapControl($form, $this->getSettings());

    // Set Map Geometries Options Element.
    $this->setMapPathOptionsElement($form, $this->getSettings());

    // Set Replacement Patterns Element.
    $this->setReplacementPatternsElement($form);

    // Set Map Geocoder Control Element, if the Geocoder Module exists,
    // otherwise output a tip on Geocoder Module Integration.
    $this->setGeocoderMapControl($form, $this->getSettings());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Determine map settings and add map element.
    $map_settings = $this->getSetting('map');
    $default_settings = self::defaultSettings();


    $input_settings = $this->getSetting('input');
    $js_settings = [];
    $map = leaflet_map_get_info($map_settings['leaflet_map'] ?? $default_settings['map']['leaflet_map']);
    $map['context'] = 'widget';

    // Get token context.
    $token_context = [
      'field' => $items,
      $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
    ];

    // Extend options to reset_map and geocoder to uniform with Leafket
    // Formatter and Leaflet View processing.
    $options = array_merge($map_settings, ['reset_map' => $this->getSetting('reset_map')], ['path' => $this->getSetting('path')], ['geocoder' => $this->getSetting('geocoder')]);

    // Set Map additional map Settings.
    $this->setAdditionalMapOptions($map, $options);

    // Attach class to wkt input element, so we can find it in js.
    $json_element_name = 'leaflet-widget-input';
    $element['value']['#attributes']['class'][] = $json_element_name;
    // Set the readonly for styling, if readonly.
    if (isset($input_settings["readonly"]) &&  $input_settings["readonly"]) {
      $element['value']['#attributes']['class'][] = "readonly";
    }

    if (!empty($map_settings['locate'])) {
      $js_settings['locate'] = TRUE;
      unset($map['settings']['center']);
    }

    // Allow other modules to add/alter the map js settings.
    $this->moduleHandler->alter('leaflet_default_widget', $map, $this);

    $element['map'] = $this->leafletService->leafletRenderMap($map, [], $map_settings['height'] . 'px');
    $element['map']['#weight'] = -1;

    $element['title']['#type'] = 'item';
    $element['title']['#title'] = $element['value']['#title'];
    $element['title']['#weight'] = -2;
    $element['value']['#title'] = $this->t('GeoJson Data');

    // Build JS settings for leaflet widget.
    $js_settings['map_id'] = $element['map']['#map_id'];
    $js_settings['jsonElement'] = '.' . $json_element_name;
    $cardinality = $items->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();
    $js_settings['multiple'] = $cardinality == 1 ? FALSE : TRUE;
    $js_settings['cardinality'] = $cardinality > 0 ? $cardinality : 0;
    $js_settings['autoCenter'] = $map_settings['auto_center'] ?? $default_settings['auto_center'];
    $js_settings['inputHidden'] = empty($input_settings['show']);
    $js_settings['inputReadonly'] = !empty($input_settings['readonly']);
    $js_settings['toolbarSettings'] = $this->getSetting('toolbar') ?? $default_settings['toolbar'];
    $js_settings['scrollZoomEnabled'] = !empty($map_settings['scroll_zoom_enabled']) ? $map_settings['scroll_zoom_enabled'] : FALSE;
    $js_settings['path'] = str_replace(["\n", "\r"], "", $this->token->replace($this->getSetting('path'), $token_context));
    $js_settings['geocoder'] = $this->getSetting('geocoder');
    $js_settings['map_position'] = $map_settings['map_position'] ?? [];

    // Leaflet.widget plugin.
    $element['map']['#attached']['library'][] = 'leaflet/leaflet-widget';

    // Settings and geo-data are passed to the widget keyed by field id.
    $element['map']['#attached']['drupalSettings']['leaflet_widget'][$element['map']['#map_id']] = $js_settings;

    // Convert default value to geoJSON format.
    if ($geom = $this->geoPhpWrapper->load($element['value']['#default_value'])) {
      $element['value']['#default_value'] = $geom->out('json');
    }

    return $element;
  }

}
