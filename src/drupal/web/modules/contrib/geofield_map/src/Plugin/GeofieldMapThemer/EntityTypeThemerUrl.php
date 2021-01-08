<?php

namespace Drupal\geofield_map\Plugin\GeofieldMapThemer;

use Drupal\geofield_map\MapThemerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geofield_map\Services\MarkerIconService;
use Drupal\Core\Entity\EntityInterface;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup geofield_map_themers_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @MapThemer(
 *   id = "geofieldmap_entity_type_url",
 *   name = @Translation("Entity Type (geofield_map) - Image Select"),
 *   description = "This Geofield Map Themer allows the Image Selection of
 * different Marker Icons based on Entity Types/Bundles.",
 *   context = {"ViewStyle"},
 *   weight = 2,
 *   markerIconSelection = {
 *    "type" = "file_uri",
 *    "configSyncCompatibility" = TRUE,
 *   },
 *   defaultSettings = {
 *    "values" = {},
 *    "legend" = {
 *      "class" = "entity-type",
 *     },
 *   }
 * )
 */
class EntityTypeThemerUrl extends MapThemerBase {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $translation_manager,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_manager,
    MarkerIconService $marker_icon_service,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $translation_manager, $renderer, $entity_manager, $marker_icon_service);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('geofield_map.marker_icon'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildMapThemerElement(array $defaults, array &$form, FormStateInterface $form_state, GeofieldGoogleMapViewStyle $geofieldMapView) {

    // Get the existing (Default) Element settings.
    $default_element = $this->getDefaultThemerElement($defaults);

    // Get the MapThemer Entity type and bundles.
    $entity_type = $geofieldMapView->getViewEntityType();
    $entity_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    // Filter the View Bundles based on the View Filtered Bundles,
    // but only if the MapThemer is working on the View base table entity type.
    $view_bundles = $this->getMapThemerEntityBundles($geofieldMapView, $entity_type, $entity_bundles);

    // Reorder the entity bundles based on existing (Default) Element settings.
    if (!empty($default_element)) {
      $weighted_bundles = [];
      foreach ($view_bundles as $bundle) {
        $weighted_bundles[$bundle] = [
          'weight' => isset($default_element[$bundle]) ? $default_element[$bundle]['weight'] : 0,
        ];
      }
      uasort($weighted_bundles, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
      $view_bundles = array_keys($weighted_bundles);
    }

    $label_alias_upload_help = $this->getLabelAliasHelp();
    $file_select_help = $this->markerIcon->getFileSelectHelp();

    // Define the Table Header variables.
    $table_settings = [
      'header' => [
        'label' => $this->t('@entity type Type/Bundle', ['@entity type' => $entity_type]),
        'label_alias' => Markup::create($this->t('Label Alias @description', [
          '@description' => $this->renderer->renderPlain($label_alias_upload_help),
        ])),
        'marker_icon' => Markup::create($this->t('Marker Icon @file_select_help', [
          '@file_select_help' => $this->renderer->renderPlain($file_select_help),
        ])),
        'image_style' => '',
      ],
      'tabledrag_group' => 'bundles-order-weight',
      'caption' => [
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'label',
          '#value' => $this->t('Icon Urls, per Entity Types'),
        ],
      ],
    ];

    // Build the Table Header.
    $element = $this->buildTableHeader($table_settings);

    foreach ($view_bundles as $k => $bundle) {

      $icon_file_uri = !empty($default_element[$bundle]['icon_file']) ? $default_element[$bundle]['icon_file'] : NULL;
      $label_value = $entity_bundles[$bundle]['label'];

      // Define the table row parameters.
      $row = [
        'id' => "[geofieldmap_entity_type_url][values][{$bundle}]",
        'label' => [
          'value' => $label_value,
          'markup' => $label_value,
        ],
        'weight' => [
          'value' => isset($default_element[$bundle]['weight']) ? $default_element[$bundle]['weight'] : $k,
          'class' => $table_settings['tabledrag_group'],
        ],
        'label_alias' => [
          'value' => isset($default_element[$bundle]['label_alias']) ? $default_element[$bundle]['label_alias'] : '',
        ],
        'icon_file_uri' => $icon_file_uri,
        'legend_exclude' => [
          'value' => isset($default_element[$bundle]['legend_exclude']) ? $default_element[$bundle]['legend_exclude'] : (count($view_bundles) > 10 ? TRUE : FALSE),
        ],
        'attributes' => ['class' => ['draggable']],
      ];

      // Builds the table row for the MapThemer.
      $element[$bundle] = $this->buildDefaultMapThemerRow($row);

    }

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(array $datum, GeofieldGoogleMapViewStyle $geofieldMapView, EntityInterface $entity, $map_theming_values) {
    $file_uri = NULL;
    if (method_exists($entity, 'bundle')) {
      $file_uri = isset($map_theming_values[$entity->bundle()]['icon_file']) && $map_theming_values[$entity->bundle()]['icon_file'] != 'none' ? $map_theming_values[$entity->bundle()]['icon_file'] : NULL;
    }
    return $this->markerIcon->getFileSelectedUrl($file_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getLegend(array $map_theming_values, array $configuration = []) {
    $legend = $this->defaultLegendHeader($configuration);
    // Get the icon image width, as result of the Legend configuration.
    $icon_width = isset($configuration['markers_width']) ? $configuration['markers_width'] : 50;

    foreach ($map_theming_values as $bundle => $value) {
      $icon_file_uri = !empty($value['icon_file']) && $value['icon_file'] != 'none' ? $value['icon_file'] : NULL;

      // Don't render legend row in case no image is associated and the plugin
      // denies to render the DefaultLegendIcon definition.
      if (!empty($value['legend_exclude']) || (empty($icon_file_uri) && !$this->renderDefaultLegendIcon())) {
        continue;
      }
      $label = isset($value['label']) ? $value['label'] : $bundle;
      $legend['table'][$bundle] = [
        'value' => [
          '#type' => 'container',
          'label' => [
            '#markup' => !empty($value['label_alias']) ? $value['label_alias'] : $label,
          ],
          '#attributes' => [
            'class' => ['value'],
          ],
        ],
        'marker' => [
          '#type' => 'container',
          'icon_file' => !empty($icon_file_uri) ? $this->markerIcon->getLegendIconFromFileUri($icon_file_uri, $icon_width) : $this->getDefaultLegendIcon(),
          '#attributes' => [
            'class' => ['marker'],
          ],
        ],
      ];
    }

    $legend['notes'] = $this->defaultLegendFooter($configuration);

    return $legend;
  }

}
