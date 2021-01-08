<?php

namespace Drupal\geofield_map\Plugin\GeofieldMapThemer;

use Drupal\geofield_map\MapThemerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield_map\Plugin\views\style\GeofieldGoogleMapViewStyle;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup geofield_map_themers_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @MapThemer(
 *   id = "geofieldmap_custom_icon",
 *   name = @Translation("Custom Icon Image File (geofield_map) - Image Upload (deprecated)"),
 *   description = "This Geofield Map Themer allows the definition of a unique
 * custom Marker Icon, valid for all the Map Markers.",
 *   context = {"ViewStyle"},
 *   weight = 0,
 *   markerIconSelection = {
 *    "type" = "managed_file",
 *    "configSyncCompatibility" = FALSE,
 *   },
 *   defaultSettings = {
 *    "values" = {},
 *    "legend" = {
 *      "class" = "custom-icon",
 *     },
 *   }
 * )
 */
class CustomIconThemer extends MapThemerBase {

  /**
   * {@inheritdoc}
   */
  public function buildMapThemerElement(array $defaults, array &$form, FormStateInterface $form_state, GeofieldGoogleMapViewStyle $geofieldMapView) {

    // Get the existing (Default) Element settings.
    $default_element = $this->getDefaultThemerElement($defaults);

    $file_upload_help = $this->markerIcon->getFileUploadHelp();
    $fid = (integer) !empty($default_element['icon_file']['fids']) ? $default_element['icon_file']['fids'] : NULL;
    $element = [
      '#markup' => Markup::create($this->t('<label>Custom Icon Image File</label>')),
      '#type' => 'container',
      'description' => [
        '#markup' => Markup::create($this->t('The chosen icon file will be used as Marker for all Geofield Map features @file_upload_help', [
          '@file_upload_help' => $this->renderer->renderPlain($file_upload_help),
        ])),
      ],
      'icon_file' => $this->markerIcon->getIconFileManagedElement($fid[0]),
      'image_style' => [
        '#type' => 'select',
        '#title' => t('Image style'),
        '#options' => $this->markerIcon->getImageStyleOptions(),
        '#default_value' => isset($default_element['image_style']) ? $default_element['image_style'] : 'geofield_map_default_icon_style',
        '#states' => [
          'visible' => [
            ':input[name="style_options[map_marker_and_infowindow][theming][geofieldmap_custom_icon][values][icon_file][is_svg]"]' => ['checked' => FALSE],
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
            ':input[name="style_options[map_marker_and_infowindow][theming][geofieldmap_custom_icon][values][icon_file][is_svg]"]' => ['checked' => FALSE],
          ],
        ],
      ],
      'label_alias' => [
        '#type' => 'textfield',
        '#title' => t('Label alias'),
        '#default_value' => isset($default_element['label_alias']) ? $default_element['label_alias'] : '',
        '#description' => $this->t('If not empty, this will be used in the legend.'),
        '#size' => 20,
      ],
    ];

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(array $datum, GeofieldGoogleMapViewStyle $geofieldMapView, EntityInterface $entity, $map_theming_values) {
    // The Custom Icon Themer plugin defines a unique icon value.
    if (!empty($map_theming_values['icon_file']['fids'])) {
      $image_style = isset($map_theming_values['image_style']) ? $map_theming_values['image_style'] : 'none';
      return $this->markerIcon->getFileManagedUrl($map_theming_values['icon_file']['fids'][0], $image_style);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegend(array $map_theming_values, array $configuration = []) {
    $legend = $this->defaultLegendHeader($configuration);

    // Get the icon image style, as result of the Legend configuration.
    $image_style = isset($configuration['markers_image_style']) ? $configuration['markers_image_style'] : 'none';
    // Get the map_theming_image_style, is so set.
    if (isset($configuration['markers_image_style']) && $configuration['markers_image_style'] == '_map_theming_image_style_') {
      $image_style = isset($map_theming_values['image_style']) ? $map_theming_values['image_style'] : 'none';
    }

    $fid = (integer) !empty($map_theming_values['icon_file']['fids']) ? $map_theming_values['icon_file']['fids'][0] : NULL;

    $legend['table']['custom-icon'] = [
      'value' => [
        '#type' => 'container',
        'label' => [
          '#markup' => !empty($map_theming_values['label_alias']) ? $map_theming_values['label_alias'] : $this->t('All Markers'),
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

    $legend['notes'] = [
      '#markup' => isset($configuration['legend_notes']) ? $configuration['legend_notes'] : '',
      '#attributes' => [
        'class' => ['notes'],
      ],
    ];

    return $legend;
  }

}
