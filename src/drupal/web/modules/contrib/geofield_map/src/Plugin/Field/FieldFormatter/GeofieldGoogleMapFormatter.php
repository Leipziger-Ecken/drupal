<?php

namespace Drupal\geofield_map\Plugin\Field\FieldFormatter;

use Drupal\geofield_map\GeofieldMapFieldTrait;
use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geofield_map\Services\GoogleMapsService;
use Drupal\Core\Render\Markup;
use Drupal\geofield_map\Services\MarkerIconService;
use Drupal\Core\Utility\Token;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Plugin implementation of the 'geofield_google_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geofield_google_map",
 *   label = @Translation("Geofield Google Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldGoogleMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use GeofieldMapFieldTrait;
  use GeofieldMapFormElementsValidationTrait;

  /**
   * Empty Map Options.
   *
   * @var array
   */
  protected $emptyMapOptions = [
    '0' => 'Empty field',
    '1' => 'Custom Message',
    '2' => 'Empty Map Centered at the Default Center',
  ];

  /**
   * The Default Settings.
   *
   * @var array
   */
  protected $defaultSettings;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;


  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\core\Utility\Token
   */
  protected $token;

  /**
   * The geofieldMapGoogleMaps service.
   *
   * @var \Drupal\geofield_map\Services\GoogleMapsService
   */
  protected $googleMapsService;

  /**
   * The Icon Managed File Service.
   *
   * @var \Drupal\geofield_map\Services\MarkerIconService
   */
  protected $markerIcon;

  /**
   * GeofieldGoogleMapFormatter constructor.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The The geoPhpWrapper.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\core\Utility\Token $token
   *   The token service.
   * @param \Drupal\geofield_map\Services\GoogleMapsService $google_maps_service
   *   The Google Maps service.
   * @param \Drupal\geofield_map\Services\MarkerIconService $marker_icon_service
   *   The Marker Icon Service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    LinkGeneratorInterface $link_generator,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager,
    GeoPHPInterface $geophp_wrapper,
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler,
    Token $token,
    GoogleMapsService $google_maps_service,
    MarkerIconService $marker_icon_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->defaultSettings = self::getDefaultSettings();
    $this->config = $config_factory;
    $this->link = $link_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
    $this->googleMapsService = $google_maps_service;
    $this->markerIcon = $marker_icon_service;
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
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('link_generator'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('geofield.geophp'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('geofield_map.google_maps'),
      $container->get('geofield_map.marker_icon')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::getDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $default_settings = self::defaultSettings();
    $settings = $this->getSettings();

    $elements = [];
    if ($this->moduleHandler->moduleExists('token')) {

      $elements['replacement_patterns'] = [
        '#type' => 'details',
        '#title' => 'Replacement patterns',
        '#description' => $this->t('The following replacement tokens are available for the "Icon Image Path" and the "Map Geometries Options" options'),
      ];

      $elements['replacement_patterns']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
      ];
    }
    else {
      $elements['replacement_patterns']['#description'] = $this->t('The @token_link is needed to browse and use @entity_type entity token replacements.', [
        '@token_link' => $this->link->generate(t('Token module'), Url::fromUri('https://www.drupal.org/project/token', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
        '@entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      ]);
    }

    $elements += $this->generateGMapSettingsForm($form, $form_state, $settings, $default_settings);

    $elements['#attached'] = [
      'library' => [
        'geofield_map/geofield_map_view_display_settings',
      ],
    ];

    // Define a specific default_icon_image_mode that consider icon_image_path
    // eventually set previously to its select introduction.
    $init_icon_image_mode = !empty($settings['map_marker_and_infowindow']['icon_image_path']) ? 'icon_image_path' : $default_settings['map_marker_and_infowindow']['icon_image_mode'];
    $default_icon_image_mode = !empty($settings['map_marker_and_infowindow']['icon_image_mode']) ? $settings['map_marker_and_infowindow']['icon_image_mode'] : $init_icon_image_mode;

    $geofield_id = $this->fieldDefinition->getName();
    $form_state->setTemporaryValue('geofield_id', $geofield_id);

    // Get the eventual ajax user input of the icon_image_mode field.
    $user_input = $form_state->getUserInput();
    $user_input_icon_image_mode = isset($user_input['fields']) && isset($user_input['fields'][$geofield_id]['settings_edit_form']) && isset($user_input['fields'][$geofield_id]['settings_edit_form']['settings']['map_marker_and_infowindow']['icon_image_mode']) ?
      $user_input['fields'][$geofield_id]['settings_edit_form']['settings']['map_marker_and_infowindow']['icon_image_mode'] : NULL;

    $selected_icon_image_mode = isset($user_input_icon_image_mode) ? $user_input_icon_image_mode : $default_icon_image_mode;

    $elements['map_marker_and_infowindow']['icon_image_mode'] = [
      '#title' => $this->t('Custom Icon definition mode'),
      '#type' => 'select',
      '#options' => [
        'icon_file' => 'Icon File',
        'icon_image_path' => 'Icon Image Path',
      ],
      '#default_value' => $selected_icon_image_mode,
      '#description' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => Markup::create('choose method between:<br><b>Icon Image Path:</b> Point the image url (absolute or relative to Drupal root folder)<br><b>Icon Image File:</b> Upload an Icon Image into Drupal application</li>'),
      ],
      '#weight' => $elements['map_marker_and_infowindow']['icon_image_path']['#weight'] - 2,
      '#ajax' => [
        'callback' => [static::class, 'iconImageModeUpdate'],
        'effect' => 'fade',
      ],
    ];

    $file_upload_help = $this->markerIcon->getFileUploadHelp();
    $fid = (integer) !empty($settings['map_marker_and_infowindow']['icon_file_wrapper']['icon_file']['fids']) ? $settings['map_marker_and_infowindow']['icon_file_wrapper']['icon_file']['fids'] : NULL;
    $elements['map_marker_and_infowindow']['icon_file_wrapper'] = [
      '#type' => 'container',
      'label' => [
        '#markup' => Markup::create($this->t('<label>Custom Icon Image File</label>')),
      ],
      'description' => [
        '#markup' => Markup::create($this->t('The chosen icon file will be used as Marker for this content @file_upload_help', [
          '@file_upload_help' => $this->renderer->renderPlain($file_upload_help),
        ])),
      ],
      'icon_file' => $this->markerIcon->getIconFileManagedElement($fid),
      'image_style' => [
        '#type' => 'select',
        '#title' => $this->t('Image style'),
        '#options' => $this->markerIcon->getImageStyleOptions(),
        '#default_value' => isset($settings['map_marker_and_infowindow']['icon_file_wrapper']['image_style']) ? $settings['map_marker_and_infowindow']['icon_file_wrapper']['image_style'] : 'geofield_map_default_icon_style',
        '#states' => [
          'visible' => [
            ':input[name="fields[field_geofield][settings_edit_form][settings][map_marker_and_infowindow][icon_file_wrapper][icon_file][is_svg]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'image_style_svg' => [
        '#type' => 'container',
        'warning' => [
          '#markup' => $this->t("Image style cannot apply to SVG Files,<br>SVG natural dimension will be applied."),
        ],
        '#states' => [
          'invisible' => [
            ':input[name="fields[field_geofield][settings_edit_form][settings][map_marker_and_infowindow][icon_file_wrapper][icon_file][is_svg]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      '#weight' => $elements['map_marker_and_infowindow']['icon_image_mode']['#weight'] + 1,
    ];

    if ($selected_icon_image_mode != 'icon_file') {
      $elements['map_marker_and_infowindow']['icon_file_wrapper']['#attributes']['class'] = ['hidden'];
    }

    if ($selected_icon_image_mode != 'icon_image_path') {
      $elements['map_marker_and_infowindow']['icon_image_path']['#prefix'] = '<div id="icon-image-path" class="visually-hidden">';
      $elements['map_marker_and_infowindow']['icon_image_path']['#suffix'] = '</div>';
    }

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * Ajax callback triggered Icon Image Option Selection.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated form element.
   */
  public static function iconImageModeUpdate(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $geofield_id = $form_state->getTemporaryValue('geofield_id');
    $response->addCommand(new ReplaceCommand(
      '#map-marker-and-infowindow-wrapper',
      $form['fields'][$geofield_id]['plugin']['settings_edit_form']['settings']['map_marker_and_infowindow']
    ));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $default_settings = self::defaultSettings();
    $settings = $this->getSettings();

    // Define a specific default_icon_image_mode that consider icon_image_path
    // eventually set previously to its select introduction.
    $default_icon_image_mode = !empty($settings['map_marker_and_infowindow']['icon_image_path']) ? 'icon_image_path' : $default_settings['map_marker_and_infowindow']['icon_image_mode'];

    $map_dimensions = [
      '#markup' => $this->t('Map Dimensions: Width: @width - Height: @height', ['@width' => $settings['map_dimensions']['width'], '@height' => $settings['map_dimensions']['height']]),
    ];

    $map_empty = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Behaviour for the Empty Map: @state', ['@state' => $this->emptyMapOptions[$settings['map_empty']['empty_behaviour']]]),
    ];

    if ($settings['map_empty']['empty_behaviour'] === '1') {
      $map_empty['message'] = [
        '#markup' => $this->t('Empty Field Message: Width: @state', ['@state' => $settings['map_empty']['empty_message']]),
      ];
    }

    $map_center = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Map Default Center: @state_lat, @state_lon', [
        '@state_lat' => $settings['map_center']['lat'],
        '@state_lon' => $settings['map_center']['lon'],
      ]),
      'center_force' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Force Map Center: @state', ['@state' => $settings['map_center']['center_force'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];
    $map_zoom_and_pan = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Zoom and Pan:') . '</u>',
      'zoom' => [
        'initial' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Start Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['initial']]),
        ],
        'force' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Force Start Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['force'] ? $this->t('Yes') : $this->t('No')]),
        ],
        'min' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Min Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['min']]),
        ],
        'max' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Max Zoom: @state', ['@state' => $settings['map_zoom_and_pan']['zoom']['max']]),
        ],
      ],
      'scrollwheel' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Scrollwheel: @state', ['@state' => $settings['map_zoom_and_pan']['scrollwheel'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'draggable' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Draggable: @state', ['@state' => $settings['map_zoom_and_pan']['draggable'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'map_reset' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Map Reset Control: @state', ['@state' => !empty($settings['map_zoom_and_pan']['map_reset']) ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    // Remove the unselected array keys
    // from the map_type_control_options_type_ids.
    $map_type_control_options_type_ids = array_filter($settings['map_controls']['map_type_control_options_type_ids'], function ($value) {
      return $value !== 0;
    });

    $map_controls = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Controls:') . '</u>',
      'disable_default_ui' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Disable Default UI: @state', ['@state' => $settings['map_controls']['disable_default_ui'] ? $this->t('Yes') : $this->t('No')]),
      ],
      'map_type_id' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Default Map Type: @state', ['@state' => $settings['map_controls']['map_type_id']]),
      ],
    ];

    if (!$settings['map_controls']['disable_default_ui']) {
      $map_controls['zoom_control'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Zoom Control: @state', ['@state' => $settings['map_controls']['zoom_control'] ? $this->t('Yes') : $this->t('No')]),
      ];
      $map_controls['map_type_control'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Map Type Control: @state', ['@state' => $settings['map_controls']['map_type_control'] ? $this->t('Yes') : $this->t('No')]),
      ];
      $map_controls['map_type_control_options_type_ids'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $settings['map_controls']['map_type_control'] ? $this->t('Enabled Map Types: @state', ['@state' => implode(', ', array_keys($map_type_control_options_type_ids))]) : '',
      ];
      $map_controls['scale_control'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Scale Control: @state', ['@state' => $settings['map_controls']['scale_control'] ? $this->t('Yes') : $this->t('No')]),
      ];
      $map_controls['street_view_control'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Streetview Control: @state', ['@state' => $settings['map_controls']['street_view_control'] ? $this->t('Yes') : $this->t('No')]),
      ];
      $map_controls['fullscreen_control'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Fullscreen Control: @state', ['@state' => $settings['map_controls']['fullscreen_control'] ? $this->t('Yes') : $this->t('No')]),
      ];
    }

    $icon_image_mode = !empty($settings['map_marker_and_infowindow']['icon_image_mode']) ? $settings['map_marker_and_infowindow']['icon_image_mode'] : $default_icon_image_mode;
    $map_marker_and_infowindow = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Map Marker and Infowindow:') . '</u>',
      'icon_image_mode' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Custom Icon definition mode: @state', ['@state' => $icon_image_mode]),
        '#weight' => 0,
      ],
      'infowindow_field' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Infowindow @state', ['@state' => !empty($settings['map_marker_and_infowindow']['infowindow_field']) ? 'from: ' . $settings['map_marker_and_infowindow']['infowindow_field'] : $this->t('disabled')]),
        '#weight' => 2,
      ],
      'tooltip_field' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Tooltip @state', ['@state' => !empty($settings['map_marker_and_infowindow']['tooltip_field']) ? 'from: ' . $settings['map_marker_and_infowindow']['tooltip_field'] : $this->t('disabled')]),
        '#weight' => 2,
      ],
      'force_open' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Open Infowindow on Load: @state', ['@state' => !empty($settings['map_marker_and_infowindow']['force_open']) ? $this->t('Yes') : $this->t('No')]),
        '#weight' => 3,
      ],
    ];

    if ($icon_image_mode == 'icon_image_path') {
      $map_marker_and_infowindow['icon_image_path'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Icon: @state', ['@state' => !empty($settings['map_marker_and_infowindow']['icon_image_path']) ? $settings['map_marker_and_infowindow']['icon_image_path'] : $this->t('Default Google Marker')]),
        '#weight' => 1,
      ];
    }

    if ($settings['map_marker_and_infowindow']['infowindow_field'] == '#rendered_entity') {
      $map_marker_and_infowindow['view_mode'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('View Mode: @state', ['@state' => $settings['map_marker_and_infowindow']['view_mode']]),
      ];
    }

    if (!empty($settings['map_additional_options'])) {
      $map_additional_options = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Map Additional Options:'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $settings['map_additional_options'],
        ],
      ];
    }

    $map_oms = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Overlapping Markers:') . '</u>',
      'map_oms_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Spiderfy overlapping markers: @state', ['@state' => $settings['map_oms']['map_oms_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    $map_markercluster = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<u>' . $this->t('Marker Clustering:') . '</u>',
      'markercluster_control' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Cluster Enabled: @state', ['@state' => isset($settings['map_markercluster']['markercluster_control']) && $settings['map_markercluster']['markercluster_control'] ? $this->t('Yes') : $this->t('No')]),
      ],
    ];

    if (!empty($settings['map_markercluster']['markercluster_additional_options'])) {
      $map_markercluster['markercluster_additional_options'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Marker Cluster Additional Options:'),
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => isset($settings['map_markercluster']['markercluster_additional_options']) ? $settings['map_markercluster']['markercluster_additional_options'] : [],
        ],
      ];
    }

    $custom_style_map = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Custom Style Map: @state', ['@state' => $settings['custom_style_map']['custom_style_control'] ? $this->t('Yes') : $this->t('No')]),
    ];

    if ($settings['custom_style_map']['custom_style_control']) {
      $custom_style_map['custom_style_name'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Custom Style Name: @state', ['@state' => $settings['custom_style_map']['custom_style_name']]),
      ];
      $custom_style_map['custom_style_default'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Custom Map Style as Default: @state', ['@state' => $settings['custom_style_map']['custom_style_default'] ? $this->t('Yes') : $this->t('No')]),
      ];
    }

    $map_lazy_load = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Lazy load map: @state', ['@state' => $settings['map_lazy_load']['lazy_load'] ? $this->t('Yes') : $this->t('No')]),
    ];

    $summary = [
      'map_google_api_key' => $this->setMapGoogleApiKeyElement(),
      'map_dimensions' => $map_dimensions,
      'map_empty' => $map_empty,
      'map_center' => $map_center,
      'map_zoom_and_pan' => $map_zoom_and_pan,
      'map_controls' => $map_controls,
      'map_marker_and_infowindow' => $map_marker_and_infowindow,
      'map_additional_options' => isset($map_additional_options) ? $map_additional_options : NULL,
      'map_oms' => $map_oms,
      'map_markercluster' => $map_markercluster,
      'custom_style_map' => $custom_style_map,
      'map_lazy_load' => $map_lazy_load,
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

    // This avoids the infinite loop by stopping the display
    // of any map embedded in an infowindow.
    $view_in_progress = &drupal_static(__FUNCTION__);
    if ($view_in_progress) {
      return [];
    }
    $view_in_progress = TRUE;

    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    // Take the entity translation, if existing.
    /* @var \Drupal\Core\TypedData\TranslatableInterface $entity */
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id();
    /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $items->getFieldDefinition();

    $map_settings = $this->getSettings();

    // Performs some preprocess on the maps settings before sending to js.
    $this->preProcessMapSettings($map_settings);

    $js_settings = [
      'mapid' => Html::getUniqueId("geofield_map_{$entity_type}_{$bundle}_{$entity_id}_{$field->getName()}"),
      'map_settings' => $map_settings,
      'data' => [],
    ];

    // Get and set the Geofield cardinality.
    $js_settings['map_settings']['geofield_cardinality'] = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Get token context.
    $token_context = [
      'field' => $items,
      $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
    ];

    $description = [];
    $description_field = isset($map_settings['map_marker_and_infowindow']['infowindow_field']) ? $map_settings['map_marker_and_infowindow']['infowindow_field'] : NULL;
    /* @var \Drupal\Core\Field\FieldItemList $description_field_entity */
    $description_field_entity = $entity->$description_field;

    // Render the entity with the selected view mode.
    if (isset($description_field) && $description_field === '#rendered_entity' && is_object($entity)) {
      $build = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $map_settings['map_marker_and_infowindow']['view_mode']);
      $description[] = $this->renderer->renderPlain($build);
    }
    // Normal rendering via fields.
    elseif (isset($description_field)) {
      if ($map_settings['map_marker_and_infowindow']['infowindow_field'] === 'title') {
        $description[] = $entity->label();
      }
      elseif (isset($entity->$description_field)) {
        $description_field_cardinality = $description_field_entity->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
        foreach ($description_field_entity->getValue() as $value) {
          $description[] = isset($value['value']) ? $value['value'] : '';
          if ($description_field_cardinality == 1 || $map_settings['map_marker_and_infowindow']['multivalue_split'] == FALSE) {
            break;
          }
        }
      }
    }

    // Define a Tooltip for the Feature.
    $tooltip = isset($map_settings['map_marker_and_infowindow']['tooltip_field']) && $map_settings['map_marker_and_infowindow']['tooltip_field'] == 'title' ? $entity->label() : '';

    $geojson_data = $this->getGeoJsonData($items, $entity->id(), $description, $tooltip);

    // Add Custom Icon, if set.
    if (isset($map_settings['map_marker_and_infowindow']['icon_image_mode'])
      && $map_settings['map_marker_and_infowindow']['icon_image_mode'] === 'icon_file') {
      $image_style = 'none';
      $fid = NULL;

      if (isset($map_settings['map_marker_and_infowindow']['icon_file_wrapper']['image_style'])) {
        $image_style = $map_settings['map_marker_and_infowindow']['icon_file_wrapper']['image_style'];
      }

      if ((integer) !empty($map_settings['map_marker_and_infowindow']['icon_file_wrapper']['icon_file']['fids'])) {
        $fid = $map_settings['map_marker_and_infowindow']['icon_file_wrapper']['icon_file']['fids'];
      }

      foreach ($geojson_data as $k => $datum) {
        if ($datum['geometry']->type === 'Point') {
          $geojson_data[$k]['properties']['icon'] = $this->markerIcon->getFileManagedUrl($fid, $image_style);
          // Flag the data with theming, for later rendering logic.
          $geojson_data[$k]['properties']['theming'] = TRUE;
        }
      }
    }
    elseif (isset($map_settings['map_marker_and_infowindow']['icon_image_mode'])
      && $map_settings['map_marker_and_infowindow']['icon_image_mode'] === 'icon_image_path') {
      foreach ($geojson_data as $k => $datum) {
        if ($datum['geometry']->type === 'Point') {
          $geojson_data[$k]['properties']['icon'] = !empty($map_settings['map_marker_and_infowindow']['icon_image_path']) ? $this->token->replace($map_settings['map_marker_and_infowindow']['icon_image_path'], $token_context) : '';
          // Flag the data with theming, for later rendering logic.
          $geojson_data[$k]['properties']['theming'] = TRUE;
        }
      }
    }

    // Associate dynamic path properties (token based) to the feature,
    // in case of not point.
    foreach ($geojson_data as $k => $datum) {
      if ($datum['geometry']->type !== 'Point') {
        $geojson_data[$k]['properties']['path_options'] = !empty($map_settings['map_geometries_options']) ? str_replace(["\n", "\r"], "", $this->token->replace($map_settings['map_geometries_options'], $token_context)) : '';
      }
    }

    if (empty($geojson_data) && $map_settings['map_empty']['empty_behaviour'] !== '2') {
      $view_in_progress = FALSE;
      return [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $map_settings['map_empty']['empty_behaviour'] === '1' ? $map_settings['map_empty']['empty_message'] : '',
        '#attributes' => [
          'class' => ['empty-geofield'],
        ],
      ];
    }
    else {
      $js_settings['data'] = [
        'type' => 'FeatureCollection',
        'features' => $geojson_data,
      ];
    }

    // Allow other modules to add/alter the map js settings.
    $this->moduleHandler->alter('geofield_map_googlemap_formatter', $js_settings, $items);

    $element = [geofield_map_googlemap_render($js_settings)];

    // Part of infinite loop stopping strategy.
    $view_in_progress = FALSE;

    return $element;
  }

}
