<?php

namespace Drupal\geofield_map\Plugin\GeofieldMapThemer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup geofield_map_themers_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @MapThemer(
 *   id = "geofieldmap_list_fields",
 *   name = @Translation("List Type Field (geofield_map) - Image Upload (deprecated)"),
 *   description = "This Geofield Map Themer allows the definition of different
 * Marker Icons based on List (Options) Type fields in View.",
 *   context = {"ViewStyle"},
 *   weight = 7,
 *   markerIconSelection = {
 *    "type" = "managed_file",
 *    "configSyncCompatibility" = FALSE,
 *   },
 *   defaultSettings = {
 *    "values" = {},
 *    "legend" = {
 *      "class" = "option",
 *     },
 *   }
 * )
 */
class ListFieldThemer extends ListFieldThemerUrl {

  /**
   * {@inheritdoc}
   */
  public function buildMapThemerElement(array $defaults, array &$form, FormStateInterface $form_state, GeofieldGoogleMapViewStyle $geofieldMapView) {

    // Get the existing (Default) Element settings.
    $default_element = $this->getDefaultThemerElement($defaults);

    // Get the View Filtered entity bundles.
    $entity_type = $geofieldMapView->getViewEntityType();
    $view_fields = $geofieldMapView->getViewFields();

    // Get the field_storage_definitions.
    $field_storage_definitions = $geofieldMapView->getEntityFieldManager()->getFieldStorageDefinitions($entity_type);

    // Get the defined List Type Fields.
    $list_fields = [];
    foreach ($view_fields as $field_id => $field_label) {
      /* @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
      if (isset($field_storage_definitions[$field_id])
        && $field_storage_definitions[$field_id] instanceof FieldStorageConfig
        && in_array($field_storage_definitions[$field_id]->getType(), [
          'list_string',
          'list_integer',
          'list_float',
        ])
        && $field_storage_definitions[$field_id]->getCardinality() == 1
      ) {
        $list_fields[$field_id] = ['options' => $field_storage_definitions[$field_id]->getSetting('allowed_values')];
      }
    }

    foreach ($list_fields as $field_id => $field_label) {
      // Reorder the field_id options on existing (Default) Element settings.
      if (!empty($default_element)) {
        // Eventually filter out the default terms that have been removed, in
        // the meanwhile.
        $default_existing_array_keys = array_intersect(array_keys($default_element['fields'][$field_id]['options']), array_keys($list_fields[$field_id]['options']));
        $list_fields[$field_id]['options'] = array_replace(array_flip($default_existing_array_keys), $list_fields[$field_id]['options']);
      }
    }

    // Define a default list_field.
    $keys = array_keys($list_fields);
    $fallback_list_field = array_shift($keys);
    $default_list_field = !empty($default_element['list_field']) ? $default_element['list_field'] : $fallback_list_field;

    // Get the eventual ajax user input of the specific list field.
    $user_input = $form_state->getUserInput();
    $user_input_list_field = isset($user_input['style_options']) && isset($user_input['style_options']['map_marker_and_infowindow']['theming']['geofieldmap_list_fields']['values']['list_field']) ?
      $user_input['style_options']['map_marker_and_infowindow']['theming']['geofieldmap_list_fields']['values']['list_field'] : NULL;

    $selected_list_field = isset($user_input_list_field) ? $user_input_list_field : $default_list_field;

    $element = [
      '#type' => 'fieldset',
      '#prefix' => '<div id="list-themer-wrapper">',
      '#suffix' => '</div>',
    ];

    if (!count($list_fields) > 0) {
      $element['list_field'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('At least a List Type field (<u>with a cardinality of 1</u>) should be added to the View to use this Map Theming option.'),
        '#attributes' => [
          'class' => ['geofield-map-warning'],
        ],
      ];
    }
    else {
      $element['list_field'] = [
        '#type' => 'select',
        '#title' => $this->t('List Type Field'),
        '#description' => $this->t('Choose the List type field to base the Map Theming upon.'),
        '#options' => array_combine(array_keys($list_fields), array_keys($list_fields)),
        '#default_value' => $selected_list_field,
        '#ajax' => [
          'callback' => [static::class, 'listFieldOptionsUpdate'],
          'effect' => 'fade',
        ],
      ];

      $label_alias_upload_help = $this->getLabelAliasHelp();
      $file_upload_help = $this->markerIcon->getFileUploadHelp();

      $element['list_field']['fields'] = [];
      foreach ($list_fields as $k => $field) {

        // Define the Table Header variables.
        $table_settings = [
          'header' => [
            'label' => $this->t('Option'),
            'label_alias' => Markup::create($this->t('Option Alias @description', [
              '@description' => $this->renderer->renderPlain($label_alias_upload_help),
            ])),
            'marker_icon' => Markup::create($this->t('Marker Icon @file_upload_help', [
              '@file_upload_help' => $this->renderer->renderPlain($file_upload_help),
            ])),
            'image_style' => Markup::create($this->t('Icon Image Style')),
          ],
          'tabledrag_group' => 'options-order-weight',
          'caption' => [
            'title' => [
              '#type' => 'html_tag',
              '#tag' => 'label',
              '#value' => $this->t('Options from  @field field', [
                '@field' => $k,
              ]),
              'notes' => [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#value' => $this->t('The - Default Value - will be used as fallback Value/Marker for unset Options'),
                '#attributes' => [
                  'style' => ['style' => 'font-size:0.8em; color: gray; font-weight: normal'],
                ],
              ],
            ],
          ],
        ];

        // Build the Table Header.
        $element['fields'][$k] = [
          '#type' => 'container',
          'options' => $this->buildTableHeader($table_settings),
        ];

        // Add a Default Value to be used as possible fallback Value/Marker.
        $field['options']['__default_value__'] = '- Default Value - ';

        $i = 0;
        foreach ($field['options'] as $id => $value) {
          $fid = (integer) !empty($default_element['fields'][$k]['options'][$id]['icon_file']['fids']) ? $default_element['fields'][$k]['options'][$id]['icon_file']['fids'] : NULL;

          // Define the table row parameters.
          $row = [
            'id' => "[geofieldmap_list_fields][values][fields][{$k}][options][{$id}]",
            'label' => [
              'value' => $value,
              'markup' => $value,
            ],
            'weight' => [
              'value' => isset($default_element['fields'][$k]['options'][$id]['weight']) ? $default_element['fields'][$k]['options'][$id]['weight'] : $i,
              'class' => $table_settings['tabledrag_group'],
            ],
            'label_alias' => [
              'value' => isset($default_element['fields'][$k]['options'][$id]['label_alias']) ? $default_element['fields'][$k]['options'][$id]['label_alias'] : '',
            ],
            'icon_file_id' => $fid,
            'image_style' => [
              'options' => $this->markerIcon->getImageStyleOptions(),
              'value' => isset($default_element['fields'][$k]['options'][$id]['image_style']) ? $default_element['fields'][$k]['options'][$id]['image_style'] : 'geofield_map_default_icon_style',
            ],
            'legend_exclude' => [
              'value' => isset($default_element['fields'][$k]['options'][$id]['legend_exclude']) ? $default_element['fields'][$k]['options'][$id]['legend_exclude'] : (count($field['options']) > 10 ? TRUE : FALSE),
            ],
            'attributes' => ['class' => ['draggable']],
          ];

          // Builds the table row for the MapThemer.
          $element['fields'][$k]['options'][$id] = $this->buildDefaultMapThemerRow($row);
          $i++;
        }

        // Hide the un-selected List Field options.
        if ($k != $selected_list_field) {
          $element['fields'][$k]['#attributes']['class'] = ['hidden'];
        }

      }
    }
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(array $datum, GeofieldGoogleMapViewStyle $geofieldMapView, EntityInterface $entity, $map_theming_values) {

    $list_field = isset($map_theming_values['list_field']) ? $map_theming_values['list_field'] : NULL;
    $fallback_icon_style = isset($map_theming_values['fields'][$list_field]['options']['__default_value__']['image_style']) ? $map_theming_values['fields'][$list_field]['options']['__default_value__']['image_style'] : NULL;
    $fallback_icon = isset($map_theming_values['fields'][$list_field]['options']['__default_value__']['icon_file']) ? $map_theming_values['fields'][$list_field]['options']['__default_value__']['icon_file']['fids'] : NULL;
    $image_style = $fallback_icon_style;
    $fid = $fallback_icon;
    if (isset($entity->{$list_field})) {
      $list_field_option = $entity->{$list_field}->value;
      $image_style = isset($map_theming_values['fields'][$list_field]['options'][$list_field_option]['image_style']) ? $map_theming_values['fields'][$list_field]['options'][$list_field_option]['image_style'] : $fallback_icon_style;
      $fid = isset($map_theming_values['fields'][$list_field]['options'][$list_field_option]['icon_file']) && !empty($map_theming_values['fields'][$list_field]['options'][$list_field_option]['icon_file']['fids']) ? $map_theming_values['fields'][$list_field]['options'][$list_field_option]['icon_file']['fids'] : $fallback_icon;
    }

    return $this->markerIcon->getFileManagedUrl($fid, $image_style);
  }

  /**
   * {@inheritdoc}
   */
  public function getLegend(array $map_theming_values, array $configuration = []) {
    $legend = $this->defaultLegendHeader($configuration);

    $list_field = $map_theming_values['list_field'];

    foreach ($map_theming_values['fields'][$list_field]['options'] as $key => $value) {

      // Get the icon image style, as result of the Legend configuration.
      $image_style = isset($configuration['markers_image_style']) ? $configuration['markers_image_style'] : 'none';
      // Get the map_theming_image_style, is so set.
      if (isset($configuration['markers_image_style']) && $configuration['markers_image_style'] == '_map_theming_image_style_') {
        $image_style = isset($map_theming_values['fields'][$list_field]['options'][$key]['image_style']) ? $map_theming_values['fields'][$list_field]['options'][$key]['image_style'] : 'none';
      }
      $fid = (integer) !empty($value['icon_file']['fids']) ? $value['icon_file']['fids'] : NULL;

      // Don't render legend row in case:
      // - the specific value is flagged as excluded from the Legend, or
      // - no image is associated and the plugin denies to render the
      // DefaultLegendIcon definition.
      if (!empty($value['legend_exclude']) || (empty($fid) && !$this->renderDefaultLegendIcon())) {
        continue;
      }
      $label = isset($value['label']) ? $value['label'] : $key;
      $legend['table'][$key] = [
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
          'icon_file' => !empty($fid) ? $this->markerIcon->getLegendIconFromFid($fid, $image_style) : $this->getDefaultLegendIcon(),
          '#attributes' => [
            'class' => ['marker'],
          ],
        ],
      ];
    }

    $legend['notes'] = $this->defaultLegendFooter($configuration);

    return $legend;
  }

  /**
   * Ajax callback triggered List Field Options Selection.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated form element.
   */
  public static function listFieldOptionsUpdate(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#list-themer-wrapper',
      $form['options']['style_options']['map_marker_and_infowindow']['theming']['geofieldmap_list_fields']['values']
    ));
    return $response;
  }

}
