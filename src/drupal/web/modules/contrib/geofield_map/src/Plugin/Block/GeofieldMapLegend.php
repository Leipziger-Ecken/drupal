<?php

namespace Drupal\geofield_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield_map\MapThemerPluginManager;
use Drupal\geofield_map\MapThemerInterface;
use Drupal\geofield_map\Services\MarkerIconService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Views;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a custom Geofield Map Legend block.
 *
 * @Block(
 *   id = "geofield_map_legend",
 *   admin_label = @Translation("Geofield Map Legend"),
 *   category = @Translation("Geofield Map")
 * )
 */
class GeofieldMapLegend extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;


  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The MapThemer Manager service .
   *
   * @var \Drupal\geofield_map\MapThemerPluginManager
   */
  protected $mapThemerManager;

  /**
   * The MapThemer Plugin used in referenced View Style.
   *
   * @var \Drupal\geofield_map\MapThemerInterface
   */
  protected $mapThemerPlugin;

  /**
   * Get the MapThemer Plugin used in referenced View Style.
   *
   * @param string $view_id
   *   The View Id.
   * @param string $view_display_id
   *   The View Display Id.
   *
   * @return array|null
   *   The MapThemer Plugin, or null.
   */
  protected function getMapThemerPluginAndValues($view_id, $view_display_id) {
    $view_displays = $this->config->get('views.view.' . $view_id)->get('display');
    if (!empty($view_displays) && !empty($view_displays[$view_display_id]) && isset($view_displays[$view_display_id]['display_options']['style'])) {
      $view_options = $view_displays[$view_display_id]['display_options']['style']['options'];
      $plugin_id = isset($view_options['map_marker_and_infowindow']['theming']) ? $view_options['map_marker_and_infowindow']['theming']['plugin_id'] : NULL;
      if (isset($plugin_id) && $plugin_id != 'none' && isset($view_options['map_marker_and_infowindow']['theming'][$plugin_id])) {
        try {
          /* @var \Drupal\geofield_map\MapThemerInterface $mapThemerPlugin */
          $plugin = $this->mapThemerManager->createInstance($plugin_id);
          $theming_values = isset($view_options['map_marker_and_infowindow']['theming'][$plugin_id]['values']) ? $view_options['map_marker_and_infowindow']['theming'][$plugin_id]['values'] : NULL;
          return [$plugin, $theming_values];
        }
        catch (\Exception $e) {
          watchdog_exception('Geofield Map Legend', $e);
          return NULL;
        }
      }
    }
  }

  /**
   * Legend Failure element..
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $failure_message
   *   The View Id.
   *
   * @return array|null
   *   The MapThemer Plugin, or null.
   */
  protected function legendFailureElement(TranslatableMarkup $failure_message = NULL) {
    if (!isset($failure_message)) {
      $failure_message = $this->t("The Legend can't be rendered");
    }
    $legend_failure = $this->currentUser->hasPermission('configure geofield_map') ? [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $failure_message,
      '#attributes' => [
        'class' => ['geofield-map-message legend-failure-message'],
      ],
    ] : [];

    return $legend_failure;
  }

  /**
   * The Icon Managed File Service.
   *
   * @var \Drupal\geofield_map\Services\MarkerIconService
   */
  protected $markerIcon;

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The Current User.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\geofield_map\MapThemerPluginManager $map_themer_manager
   *   The mapThemerManager service.
   * @param \Drupal\geofield_map\Services\MarkerIconService $marker_icon_service
   *   The Marker Icon Service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user,
    LinkGeneratorInterface $link_generator,
    RendererInterface $renderer,
    MapThemerPluginManager $map_themer_manager,
    MarkerIconService $marker_icon_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory;
    $this->currentUser = $current_user;
    $this->link = $link_generator;
    $this->renderer = $renderer;
    $this->mapThemerManager = $map_themer_manager;
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
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('link_generator'),
      $container->get('renderer'),
      $container->get('plugin.manager.geofield_map.themer'),
      $container->get('geofield_map.marker_icon')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // Attach Geofield Map Libraries.
    $form['#attached']['library'][] = 'geofield_map/geofield_map_general';
    $form['#attached']['library'][] = 'geofield_map/geofield_map_legend';

    $user_input = $form_state->getUserInput();
    $user_selected_geofield_map_legend = !empty($user_input['settings']['geofield_map_legend']) ? $user_input['settings']['geofield_map_legend'] : NULL;

    $geofield_map_legends = $this->getGeofieldMapLegends();
    $selected_geofield_map_legend = isset($user_selected_geofield_map_legend) ? $user_selected_geofield_map_legend : (isset($this->configuration['geofield_map_legend']) && array_key_exists($this->configuration['geofield_map_legend'], $geofield_map_legends) ? $this->configuration['geofield_map_legend'] : 'none:none');
    list($view_id, $view_display_id) = explode(':', $selected_geofield_map_legend);
    $map_themer_plugin_and_values = $this->getMapThemerPluginAndValues($view_id, $view_display_id);

    $map_themer_plugin = NULL;
    if (!empty($map_themer_plugin_and_values)) {
      /* @var \Drupal\geofield_map\MapThemerInterface $map_themer_plugin */
      $map_themer_plugin = $map_themer_plugin_and_values[0];
      $map_themer_plugin_definition = $map_themer_plugin->getPluginDefinition();
    }

    $geofield_map_legends_options = array_merge(['none:none' => '_ none _'], $geofield_map_legends);

    $form['#prefix'] = '<div id="geofield-map-legend-settings-block-wrapper">';
    $form['#suffix'] = '</div>';

    if (!empty($geofield_map_legends)) {
      $form['geofield_map_legend'] = [
        '#type' => 'select',
        '#title' => $this->t('Geofield Map Legend'),
        '#description' => $this->t('Select the Geofield Map legend to render in this block<br>Choose the View and the Display you want to grab the Legend definition from.'),
        '#options' => $geofield_map_legends_options,
        '#default_value' => $selected_geofield_map_legend ?: 'none',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [static::class, 'mapLegendSelectionUpdate'],
          'effect' => 'fade',
        ],
      ];

      $form['values_label'] = [
        '#title' => $this->t('Values Column Label'),
        '#type' => 'textfield',
        '#description' => $this->t('Set the Label text to be shown for the Values column. Empty for any Label.'),
        '#default_value' => isset($this->configuration['values_label']) ? $this->configuration['values_label'] : $this->t('Value'),
        '#size' => 26,
      ];

      $form['markers_label'] = [
        '#title' => $this->t('Markers Column Label'),
        '#type' => 'textfield',
        '#description' => $this->t('Set the Label text to be shown for the Markers/Icon column. Empty for any Label.'),
        '#default_value' => isset($this->configuration['markers_label']) ? $this->configuration['markers_label'] : $this->t('Marker/Icon'),
        '#size' => 26,
      ];

      // Define the list of possible legend icon image style.
      $markers_image_style_options = array_merge([
        '_map_theming_image_style_' => '<- Reflect the Map Theming Icon Image Styles ->',
      ], $this->markerIcon->getImageStyleOptions());

      // Force add the geofield_map_default_icon_style, if not existing.
      if (!in_array('geofield_map_default_icon_style', array_keys($markers_image_style_options))) {
        $markers_image_style_options['geofield_map_default_icon_style'] = 'geofield_map_default_icon_style';
      }

      if ($map_themer_plugin instanceof MapThemerInterface && $map_themer_plugin_definition['markerIconSelection']['type'] == 'managed_file') {
        $form['markers_image_style'] = [
          '#type' => 'select',
          '#title' => $this->t('Markers Image style'),
          '#options' => $markers_image_style_options,
          '#default_value' => isset($this->configuration['markers_image_style']) ? $this->configuration['markers_image_style'] : 'geofield_map_default_icon_style',
          '#description' => $this->t('Choose the image style the markers icons will be rendered in the Legend with.'),
        ];
      }

      if ($map_themer_plugin instanceof MapThemerInterface && $map_themer_plugin_definition['markerIconSelection']['type'] == 'file_uri') {
        $form['markers_width'] = [
          '#type' => 'number',
          '#title' => $this->t('Markers Width (pixels)'),
          '#default_value' => isset($this->configuration['markers_width']) ? $this->configuration['markers_width'] : 50,
          '#description' => $this->t('Choose the max image width for the marker in the legend.<br>(Empty value for natural image weight.)'),
          '#min' => 10,
          '#max' => 300,
          '#size' => 4,
          '#step' => 5,
        ];
      }

      $form['legend_caption'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Legend Caption'),
        '#description' => $this->t('Write here the Table Legend Caption).'),
        '#default_value' => isset($this->configuration['legend_caption']) ? $this->configuration['legend_caption'] : '',
        '#rows' => 1,
      ];

      $form['legend_notes'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Legend Notes'),
        '#description' => $this->t("Write here Notes to the Legend (Footer as default, might be altered in the Map Themer plugin)."),
        '#default_value' => isset($this->configuration['legend_notes']) ? $this->configuration['legend_notes'] : '',
        '#rows' => 3,
      ];
    }
    else {
      $form['geofield_map_legend_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('No eligible Geofield Map View Style and Theming have been defined/found.<br>Please @define_view_link with Geofield Map View Style and (not null) Theming to be able to choose at least a Legend to render.', [
          '@define_view_link' => $this->link->generate($this->t('define one View'), Url::fromRoute('entity.view.collection')),
        ]),
        '#attributes' => [
          'class' => ['geofield-map-warning'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $legend = [];
    $build = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['geofield_map/geofield_map_general'],
      ],
    ];
    $selected_geofield_map_legend = $this->configuration['geofield_map_legend'];
    if (!empty($selected_geofield_map_legend)) {
      list($view_id, $view_display_id) = explode(':', $selected_geofield_map_legend);
      $failure_message = $this->t("The Legend can't be rendered because the chosen [@view_id:@view_display_id] view & view display combination don't exists or correspond to a valid Geofield Map Legend anymore. <u>Please reconfigure this Geofield Map Legend block consequently.</u>", [
        '@view_id' => $view_id,
        '@view_display_id' => $view_display_id,
      ]);
      $legend_failure = $this->legendFailureElement($failure_message);
      $map_themer_plugin_and_values = $this->getMapThemerPluginAndValues($view_id, $view_display_id);
      if (!empty($map_themer_plugin_and_values)) {
        /* @var \Drupal\geofield_map\MapThemerInterface $map_themer_plugin */
        list($map_themer_plugin, $theming_values) = $map_themer_plugin_and_values;
        $legend = !empty($theming_values) ? $map_themer_plugin->getLegend($theming_values, $this->configuration) : $this->legendFailureElement($this->t('The legend cannot be rendered due to a wrong setup of the Map Themer in the ViewStyle'));
      }
      else {
        $legend = $legend_failure;
      }
    }

    $build['legend'] = $legend;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['geofield_map_legend'] = $form_state->getValue('geofield_map_legend');
    $this->configuration['legend_caption'] = $form_state->getValue('legend_caption');
    $this->configuration['legend_notes'] = $form_state->getValue('legend_notes');
    $this->configuration['values_label'] = $form_state->getValue('values_label');
    $this->configuration['markers_label'] = $form_state->getValue('markers_label');
    $this->configuration['markers_image_style'] = $form_state->getValue('markers_image_style');
    $this->configuration['markers_width'] = $form_state->getValue('markers_width');
  }

  /**
   * Get elegible Geofield Map legends.
   *
   * Find of Geofield Map Views Styles where a theming has
   * been defined and outputs them in the form of view_id:view_display_id array
   * list.
   *
   * @return array
   *   The legends list.
   */
  protected function getGeofieldMapLegends() {
    $geofield_legends = [];
    $enabled_views = Views::getEnabledViews();
    /* @var \Drupal\views\Entity\View $view */
    foreach ($enabled_views as $view_id => $view) {
      foreach ($this->config->get('views.view.' . $view_id)->get('display') as $id => $view_display) {
        if (isset($view_display['display_options']['style']) && $view_display['display_options']['style']['type'] == 'geofield_google_map') {
          $view_options = $view_display['display_options']['style']['options'];
          $plugin_id = isset($view_options['map_marker_and_infowindow']['theming']) ? $view_options['map_marker_and_infowindow']['theming']['plugin_id'] : NULL;
          if (isset($plugin_id) && $plugin_id != 'none') {
            $geofield_legends[$view_id . ':' . $id] = $view->label() . ' - display: ' . $id;
          }
        }
      }
    }
    return $geofield_legends;
  }

  /**
   * Ajax callback triggered by Geofield Map Legend Selection.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated form element.
   */
  public static function mapLegendSelectionUpdate(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#geofield-map-legend-settings-block-wrapper',
      $form['settings']
    ));
    return $response;
  }

}
