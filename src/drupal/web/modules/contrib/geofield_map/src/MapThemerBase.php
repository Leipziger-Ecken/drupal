<?php

namespace Drupal\geofield_map;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;
use Drupal\geofield_map\Services\MarkerIconService;
use Drupal\Core\Entity\EntityType as ViewEntityType;

/**
 * A base class for MapThemer plugins.
 */
abstract class MapThemerBase extends PluginBase implements MapThemerInterface, ContainerFactoryPluginInterface {

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
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * The Icon Managed File Service.
   *
   * @var \Drupal\geofield_map\Services\MarkerIconService
   */
  protected $markerIcon;

  /**
   * Returns Default Table Header.
   *
   * @param array $table_settings
   *   The Table base settings..
   *
   * @return array
   *   The Default Table Header render array.
   */
  protected function buildTableHeader(array $table_settings) {

    return [
      '#type' => 'table',
      '#header' => [
        $table_settings['header']['label'],
        $this->t('Weight'),
        $table_settings['header']['label_alias'],
        $table_settings['header']['marker_icon'],
        $table_settings['header']['image_style'],
        $this->t('Hide from Legend'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $table_settings['tabledrag_group'],
        ],
      ],
      '#caption' => $this->renderer->renderPlain($table_settings['caption']),
    ];
  }

  /**
   * Builds the base table row for multivalue MapThemer.
   *
   * @param array $row
   *   The row configuration.
   *
   * @return array
   *   The table row render array.
   */
  protected function buildDefaultMapThemerRow(array $row) {

    $element = [
      'label' => [
        '#type' => 'value',
        '#value' => $row['label']['value'],
        'markup' => [
          '#markup' => $row['label']['markup'],
        ],
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => '',
        '#title_display' => 'invisible',
        '#default_value' => $row['weight']['value'],
        '#delta' => 20,
        '#attributes' => ['class' => [$row['weight']['class']]],
      ],
      'label_alias' => [
        '#type' => 'textfield',
        '#default_value' => $row['label_alias']['value'],
        '#size' => 30,
        '#maxlength' => 128,
      ],
      'icon_file' => array_key_exists('icon_file_id', $row) ? $this->markerIcon->getIconFileManagedElement($row['icon_file_id'], $row['id']) :
      (array_key_exists('icon_file_uri', $row) ? $this->markerIcon->getIconFileSelectElement($row['icon_file_uri'], $row['id']) : NULL),
      'image_style' => array_key_exists('icon_file_id', $row) ? [
        '#type' => 'select',
        '#title' => t('Image style'),
        '#title_display' => 'invisible',
        '#options' => $row['image_style']['options'],
        '#default_value' => $row['image_style']['value'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[map_marker_and_infowindow][theming]' . $row['id'] . '[icon_file][is_svg]"]' => ['checked' => FALSE],
          ],
        ],
      ] : [],
      // @TODO: Monitor this core issue that prevents correct legend_exclude default
      // value via ajax:
      // Checkboxes default value is ignored by forms system during processing
      // of AJAX request (https://www.drupal.org/project/drupal/issues/1100170)
      'legend_exclude' => [
        '#type' => 'checkbox',
        '#default_value' => $row['legend_exclude']['value'],
        '#return_value' => 1,
      ],
      'image_style_svg' => array_key_exists('icon_file_id', $row) ? [
        '#type' => 'container',
        'warning' => [
          '#markup' => $this->t("Image style cannot apply to SVG Files,<br>SVG natural dimension will be applied."),
        ],
        '#states' => [
          'invisible' => [
            ':input[name="style_options[map_marker_and_infowindow][theming]' . $row['id'] . '[icon_file][is_svg]"]' => ['checked' => FALSE],
          ],
        ],
      ] : [],
      '#attributes' => $row['attributes'],
    ];

    return $element;
  }

  /**
   * Returns the default Icon output for the Legend.
   *
   * @return array
   *   The DefaultLegendIcon render array.
   */
  protected function getDefaultLegendIcon() {
    return [
      '#type' => 'container',
      'markup' => [
        '#markup' => $this->t('[default-icon]'),
      ],
      '#attributes' => [
        'class' => ['default-icon'],
      ],
    ];
  }

  /**
   * Returns the default Legend Header.
   *
   * @param array $configuration
   *   The Legend configuration.
   *
   * @return array
   *   The DefaultLegendIcon render array.
   */
  protected function defaultLegendHeader(array $configuration) {

    $legend_default_settings = $this->defaultSettings('legend');

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['geofield-map-legend', $legend_default_settings['class']],
      ],
      'table' => [
        '#type' => 'table',
        '#caption' => isset($configuration['legend_caption']) ? $configuration['legend_caption'] : '',
        '#header' => [
          isset($configuration['values_label']) ? $configuration['values_label'] : '',
          isset($configuration['markers_label']) ? $configuration['markers_label'] : '',
        ],
      ],
    ];
  }

  /**
   * Returns the default Legend Footer.
   *
   * @param array $configuration
   *   The Legend configuration.
   *
   * @return array
   *   The DefaultLegendIcon render array.
   */
  protected function defaultLegendFooter(array $configuration) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => isset($configuration['legend_notes']) ? $configuration['legend_notes'] : '',
      '#attributes' => [
        'class' => ['notes'],
      ],
    ];
  }

  /**
   * Gets the Map Themer Entity Bundles, based on the View Style Entity Type.
   *
   * @param \Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle $geofieldMapView
   *   The Geofield Map View.
   * @param string $entity_type
   *   The entity type.
   * @param array $entity_bundles
   *   The entity bundles.
   *
   * @return array
   *   The eventually filtered Entity Bundles for the the Map Themer.
   */
  protected function getMapThemerEntityBundles(GeofieldGoogleMapViewStyle $geofieldMapView, $entity_type, array $entity_bundles): array {
    $view_bundles = $entity_type instanceof ViewEntityType && $geofieldMapView->getViewEntityType() == $entity_type->id() && !empty($geofieldMapView->getViewFilteredBundles()) ? $geofieldMapView->getViewFilteredBundles() : array_keys($entity_bundles);
    return $view_bundles;
  }

  /**
   * Generate Label Alias Help Message.
   *
   * @return array
   *   The label alias help render array..
   */
  public function getLabelAliasHelp() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('If not empty, this will be used in the legend.'),
      '#attributes' => [
        'style' => ['style' => 'font-size:0.8em; color: gray; font-weight: normal'],
      ],
    ];
  }

  /**
   * Define if to return the default Legend Icon.
   *
   * This might act on values to which no image/Managed_file has been input.
   * This might be overridden by MapThemer plugins to alter this default
   * behaviour.
   *
   * @return bool
   *   If the default Legend Icon cases/values should be listed in the legend.
   */
  protected function renderDefaultLegendIcon() {
    return TRUE;
  }

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\geofield_map\Services\MarkerIconService $marker_icon_service
   *   The Marker Icon Service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $translation_manager,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_manager,
    MarkerIconService $marker_icon_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->setStringTranslation($translation_manager);
    $this->renderer = $renderer;
    $this->entityManager = $entity_manager;
    $this->markerIcon = $marker_icon_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('geofield_map.marker_icon')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings($k = NULL) {
    $default_settings = $this->pluginDefinition['defaultSettings'];
    if (!empty($k)) {
      return $default_settings[$k];
    }
    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThemerElement(array $defaults) {
    $default_value = !empty($defaults['map_marker_and_infowindow']['theming'][$this->pluginId]['values']) ? $defaults['map_marker_and_infowindow']['theming'][$this->pluginId]['values'] : $this->defaultSettings('values');
    return $default_value;
  }

}
