<?php

namespace Drupal\geofield_map_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geofield_map\GeofieldMapFieldTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'geofield_static_google_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_static_google_map",
 *   label = @Translation("Geofield Google Map (static)"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldGoogleStaticMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use GeofieldMapFieldTrait;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * GeofieldStaticGoogleMapFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LinkGeneratorInterface $link_generator
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->link = $link_generator;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 200,
      'height' => 200,
      'scale' => 2,
      'zoom' => 13,
      'langcode' => 'en',
      'static_map_type' => 'roadmap',
      'marker_color' => '#ff0000',
      'marker_size' => 'normal',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatterIntro() {
    return $this->t("Renders a Google Map, according to the @map_static_api_link.<br>Note: <u>Only Points supported</u>, and not Geometries (Polylines, Polygons, etc.).", [
      '@map_static_api_link' => $this->link->generate($this->t('Google Maps Static API'), Url::fromUri('https://developers.google.com/maps/documentation/maps-static/dev-guide', [
        'absolute' => TRUE,
        'attributes' => ['target' => 'blank'],
      ])),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $settings = $this->getSettings();

    $elements['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->getFormatterIntro(),
    ];

    // Set Google Api Key Element.
    $elements['map_google_api_key'] = $this->setMapGoogleApiKeyElement();

    $elements['gmaps_api_link_markup'] = [
      '#markup' => $this->t('The following settings comply with the @gmaps_api_link.', [
        '@gmaps_api_link' => $this->link->generate($this->t('Google Maps Static API'), Url::fromUri('https://developers.google.com/maps/documentation/maps-static/dev-guide#introduction', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Map width'),
      '#default_value' => $settings['width'],
      '#size' => 10,
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('The width of the map, in pixels.'),
      '#required' => TRUE,
    ];

    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Map height'),
      '#default_value' => $settings['height'],
      '#size' => 10,
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('The height of the map, in pixels.'),
      '#required' => TRUE,
    ];

    $elements['scale'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#default_value' => $settings['scale'],
      '#options' => [
        '1' => '1',
        '2' => '2',
        '4' => '4',
      ],
      '#description' => $this->t('The size of the image will be multiplied by this factor, which is useful for ensuring retina-capable displays show the correct size.<br>Note that maximum size restrictions exist in Google Static Maps API.<br>Refer to the @image_sizes_link if you are unsure what values to use here.', [
        ':doc_url' => 'https://developers.google.com/maps/documentation/maps-static/dev-guide#Imagesizes',
        '@image_sizes_link' => $this->link->generate($this->t('Image Sizes documentation'), Url::fromUri('https://developers.google.com/maps/documentation/maps-static/dev-guide#Imagesizes', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    $elements['zoom'] = [
      '#type' => 'number',
      '#title' => $this->t('Map zoom'),
      '#default_value' => $settings['zoom'],
      '#size' => 10,
      '#min' => 1,
      '#step' => 1,
      '#max' => 20,
      '#description' => $this->t("The zoom level. Must be an integer between 1 and 20.<br>Note: This will be ignored in case of multiple markers, and the map will extend to markers bounds.."),
      '#required' => TRUE,
    ];

    $elements['static_map_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Map type'),
      '#default_value' => $settings['static_map_type'],
      '#options' => $this->getStaticMapOptions(),
    ];

    $elements['marker_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Marker Color'),
      '#default_value' => $settings['marker_color'],
      '#description' => $this->t("Accepts an HEX cod color.<br>Examples: #ff0000 (red), #00ff00 (green), #0000ff (blu).<br>Leave empty for default value fallback (red).<br>The value wiil be converted to comply with Google @marker_styles_link.", [
        '@marker_styles_link' => $this->link->generate($this->t('Marker Styles documentation'), Url::fromUri('https://developers.google.com/maps/documentation/maps-static/dev-guide#MarkerStyles', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    $elements['marker_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Marker Size'),
      '#default_value' => $settings['marker_size'],
      '#options' => [
        'normal' => 'normal',
        'tiny' => 'tiny',
        'mid' => 'mid',
        'small' => 'small',
      ],
      '#description' => $this->t("Refer to the @marker_styles_link if you are unsure what values to use here", [
        '@marker_styles_link' => $this->link->generate($this->t('Marker Styles documentation'), Url::fromUri('https://developers.google.com/maps/documentation/maps-static/dev-guide#MarkerStyles', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
    ];

    return $elements;
  }

  /**
   * Retrieves options for the static map type.
   *
   * @return array
   *   An associative array of map types where keys are type machine names, and
   *   values are their labels. The types are defined in
   *   https://developers.google.com/maps/documentation/maps-static/dev-guide#MapTypes
   */
  protected function getStaticMapOptions() {
    return [
      'roadmap' => $this->t('Roadmap'),
      'satellite' => $this->t('Satellite'),
      'terrain' => $this->t('Terrain'),
      'hybrid' => $this->t('Hybrid'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $map_types = $this->getStaticMapOptions();
    $summary = [
      'formatter_intro' => $this->getFormatterIntro(),
      'map_google_api_key' => $this->setMapGoogleApiKeyElement(),
      'map_dimensions' => $this->t('Map dimensions: @width x @height', [
        '@width' => $settings['width'],
        '@height' => $settings['height'],
      ]),
      'zoom_level' => $this->t('Zoom level: @zoom', [
        '@zoom' => $settings['zoom'],
      ]),
      'map_type' => $this->t('Map type: <em>@type</em>', [
        '@type' => $map_types[$settings['static_map_type']],
      ]),
      'marker_color' => $this->t('Markers Size: @marker_size', [
        '@marker_size' => $settings['marker_size'],
      ]),
      'marker_size' => $this->t('Markers Size: @marker_size', [
        '@marker_size' => $settings['marker_size'],
      ]),
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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $locations = [];
    $settings = $this->getSettings();
    $language = ($langcode !== Language::LANGCODE_NOT_SPECIFIED) ? $langcode : 'en';
    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }
      $value = $item->getValue();
      if ($value['geo_type'] !== 'Point') {
        continue;
      }
      $locations[] = urlencode($value['latlon']);
    }

    $elements = [];
    // Return a single item.
    $elements[0] = [
      '#theme' => 'geofield_static_google_map',
      '#width' => $settings['width'],
      '#height' => $settings['height'],
      '#scale' => $settings['scale'],
      '#locations' => $locations,
      '#zoom' => $settings['zoom'],
      '#langcode' => $language,
      '#static_map_type' => $settings['static_map_type'],
      '#apikey' => (string) $this->getGmapApiKey(),
      '#marker_color' => $settings['marker_color'],
      '#marker_size' => $settings['marker_size'],
    ];

    return $elements;
  }

}
