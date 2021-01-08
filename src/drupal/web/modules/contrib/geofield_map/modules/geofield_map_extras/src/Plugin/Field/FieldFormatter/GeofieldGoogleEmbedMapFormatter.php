<?php

namespace Drupal\geofield_map_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geofield_map\GeofieldMapFieldTrait;
use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'geofield_embed_google_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_embed_google_map",
 *   label = @Translation("Geofield Google Map (embed)"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldGoogleEmbedMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use GeofieldMapFieldTrait;
  use GeofieldMapFormElementsValidationTrait;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Json Encoder Service.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $encoder;

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
   * @param \Drupal\Component\Serialization\Json $encoder
   *   The Json Encoder Service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LinkGeneratorInterface $link_generator,
    Json $encoder
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->link = $link_generator;
    $this->encoder = $encoder;
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
      $container->get('link_generator'),
      $container->get('serialization.json')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 200,
      'height' => 200,
      'optionals_parameters' => '{"zoom":15,"maptype":"roadmap"}',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormatterIntro() {
    return $this->t("Renders a Google Map, according to the @map_embed_api_link.<br>Note: <u>Only 'Place' mode and Points supported</u>, and not Geometries (Polylines, Polygons, etc.)", [
      '@map_embed_api_link' => $this->link->generate($this->t('Google Maps Embed API'), Url::fromUri('https://developers.google.com/maps/documentation/embed/guide', [
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

    $elements['optionals_parameters'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Optional parameters'),
      '#default_value' => $settings['optionals_parameters'],
      '#description' => $this->t('An object literal of options, that comply with the <strong>Google Maps Embed API Library</strong> (@see link above).<br>The syntax should respect the javascript object notation (json) format.<br>Always use double quotes (") both for the indexes and the string values.'),
      '#placeholder' => self::defaultSettings()['optionals_parameters'],
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [
      'formatter_intro' => $this->getFormatterIntro(),
      'map_google_api_key' => $this->setMapGoogleApiKeyElement(),
      'map_dimensions' => $this->t('Map dimensions: @width x @height', [
        '@width' => $settings['width'],
        '@height' => $settings['height'],
      ]),
      'optionals_parameters' => $this->t('Optionals Parameters: @options', [
        '@options' => $settings['optionals_parameters'],
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
    $elements = [];
    $settings = $this->getSettings();
    $bundle = $items->getParent()->getEntity()->bundle();

    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }
      $value = $item->getValue();
      if ($value['geo_type'] !== 'Point') {
        continue;
      }
      $q = urlencode($value['latlon']);

      $options_string = '';
      $optionals_parameters = Json::decode($settings['optionals_parameters']);
      if (count($optionals_parameters)) {
        foreach ($optionals_parameters as $k => $option) {
          $options_string .= "&" . $k . "=" . $option;
        }
      }

      $elements[$delta] = [
        '#theme' => 'geofield_embed_google_map',
        '#width' => $settings['width'],
        '#height' => $settings['height'],
        '#apikey' => (string) $this->getGmapApiKey(),
        '#q' => $q,
        '#options_string' => $options_string,
        '#title' => $this->t('Map of @bundle', ['@bundle' => $bundle]),
      ];
    }

    return $elements;
  }

}
