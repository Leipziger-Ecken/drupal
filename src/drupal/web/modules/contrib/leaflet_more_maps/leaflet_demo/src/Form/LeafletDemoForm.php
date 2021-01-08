<?php

namespace Drupal\leaflet_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\leaflet\LeafletService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebformJira.
 *
 * @package Drupal\webform_jira\Form
 */
class LeafletDemoForm extends FormBase {

  const LEAFLET_DEMO_DEFAULT_LAT = 51.4777;
  const LEAFLET_DEMO_DEFAULT_LNG = -0.0015;
  const LEAFLET_DEMO_DEFAULT_ZOOM = 11;

  /**
   * The leaflet Service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Drupal Render Service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Returns the form id.
   */
  public function getFormId() {
    return 'leaflet_demo_page';
  }

  /**
   * LeafletDemoPage constructor.
   *
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet Map service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The drupal render service.
   */
  public function __construct(LeafletService $leaflet_service, Renderer $renderer) {
    $this->leafletService = $leaflet_service;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('leaflet.service'),
      $container->get('renderer')
    );
  }

  /**
   * Submits the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state['storage']['latitude']  = $form_state['values']['latitude'];
    $form_state['storage']['longitude'] = $form_state['values']['longitude'];
    $form_state['storage']['zoom'] = $form_state['values']['zoom'];
    $form_state['rebuild'] = TRUE;

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $values = $form_state->getStorage();
    if (!empty($values['latitude'])) {
      $latitude  = $values['latitude'];
      $longitude = $values['longitude'];
    }
    else {
      $latitude  = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LAT;
      $longitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LNG;
    }
    $zoom = isset($values['zoom']) ? $values['zoom'] : LeafletDemoForm::LEAFLET_DEMO_DEFAULT_ZOOM;

    $form['map_parameters'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Map parameters'),
      '#description' => $this->t('All maps below are centered on the same latitude, longitude and have the same initial zoom level.<br/>You may pan/drag and zoom each map individually.'),
    ];
    $form['map_parameters']['latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      // '#field_suffix' => $this->t('degrees'),
      '#description' => $this->t('-90 .. 90 degrees'),
      '#size' => 12,
      '#default_value' => $latitude,
    ];
    $form['map_parameters']['longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      // '#field_suffix' => $this->t('degrees'),
      '#description' => $this->t('-180 .. 180 degrees'),
      '#size' => 12,
      '#default_value' => $longitude,
    ];
    $form['map_parameters']['zoom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom'),
      '#field_suffix' => $this->t('(0..18)'),
      '#description' => $this->t('Some zoom levels may not be available in some maps.'),
      '#size' => 2,
      '#default_value' => $zoom,
    ];
    $form['map_parameters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit map parameters'),
    ];

    $form['#attached']['library'][] = 'leaflet_demo/leaflet_demo_form';

    $form['demo_maps'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['leaflet-gallery-map']],
    ];

    $form['demo_maps'] = array_merge($form['demo_maps'], $this->outputDemoMaps($latitude, $longitude, $zoom));

    return $form;
  }

  /**
   * Outputs the HTML for available Leaflet maps, centered on supplied coords.
   *
   * @param string $latitude
   *   The latitude.
   * @param string $longitude
   *   The longitude.
   *
   * @return array
   *   the map string as rendered html
   */
  protected function outputDemoMaps($latitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LAT, $longitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LNG, $zoom = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_ZOOM) {

    $demo_maps = [];

    if (!is_numeric($latitude) || !is_numeric($longitude) || !is_numeric($zoom)) {
      return [];
    }
    $center = ['lat' => $latitude, 'lon' => $longitude, 'zoom' => $zoom];
    $features = [
      'type' => 'point',
      'lat' => $latitude,
      'lon' => $longitude,
      'popup' => 'Your auto-retrieved or manually entered location',
    ];

    $map_info = leaflet_map_get_info();
    foreach ($map_info as $map_id => $map) {
      $title = $map_info[$map_id]['label'];
      // This will generate a unique id.
      $map['settings']['map_position'] = $center;
      $map['id'] = $map_id;
      $features['leaflet_id'] = $map_id;

      $render_object = $this->leafletService->leafletRenderMap($map, $features, '350px');
      $output = $this->renderer->render($render_object, FALSE);

      $demo_maps[$map_id] = [
        '#type' => 'item',
        '#title' => $title,
        '#markup' => $output,
        '#attributes' => ['class' => ['leaflet-gallery-map']],
      ];
    }

    return $demo_maps;
  }

}
