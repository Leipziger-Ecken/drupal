<?php

namespace Drupal\geofield\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Plugin implementation of the 'geofield' field type.
 *
 * @FieldType(
 *   id = "geofield",
 *   label = @Translation("Geofield"),
 *   description = @Translation("This field stores geospatial information."),
 *   default_widget = "geofield_latlon",
 *   default_formatter = "geofield_default"
 * )
 */
class GeofieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'backend' => 'geofield_backend_default',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'backend' => 'geofield_backend_default',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    /* @var \Drupal\geofield\Plugin\GeofieldBackendManager $backend_manager */
    $backend_manager = \Drupal::service('plugin.manager.geofield_backend');
    $backend_plugin = NULL;

    try {
      /* @var \Drupal\geofield\Plugin\GeofieldBackendPluginInterface $backend_plugin */
      if (!empty($field->getSetting('backend')) && $backend_manager->getDefinition($field->getSetting('backend')) != NULL) {
        $backend_plugin = $backend_manager->createInstance($field->getSetting('backend'));
      }
    }
    catch (\Exception $e) {
      watchdog_exception("geofiedl_backend_manager", $e);
      try {
        $backend_plugin = $backend_manager->createInstance('geofield_backend_default');
      }
      catch (PluginException $e) {
        watchdog_exception("geofiedl_backend_manager", $e);
      }
    }
    return [
      'columns' => [
        'value' => isset($backend_plugin) ? $backend_plugin->schema() : [],
        'geo_type' => [
          'type' => 'varchar',
          'default' => '',
          'length' => 64,
        ],
        'lat' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'lon' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'left' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'top' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'right' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'bottom' => [
          'type' => 'numeric',
          'precision' => 18,
          'scale' => 12,
          'not null' => FALSE,
        ],
        'geohash' => [
          'type' => 'varchar',
          'length' => GEOFIELD_GEOHASH_LENGTH,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'lat' => ['lat'],
        'lon' => ['lon'],
        'top' => ['top'],
        'bottom' => ['bottom'],
        'left' => ['left'],
        'right' => ['right'],
        'geohash' => ['geohash'],
        'centroid' => ['lat', 'lon'],
        'bbox' => ['top', 'bottom', 'left', 'right'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Geometry'))
      ->addConstraint('GeoType', []);

    $properties['geo_type'] = DataDefinition::create('string')
      ->setLabel(t('Geometry Type'));

    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Centroid Latitude'));

    $properties['lon'] = DataDefinition::create('float')
      ->setLabel(t('Centroid Longitude'));

    $properties['left'] = DataDefinition::create('float')
      ->setLabel(t('Left Bounding'));

    $properties['top'] = DataDefinition::create('float')
      ->setLabel(t('Top Bounding'));

    $properties['right'] = DataDefinition::create('float')
      ->setLabel(t('Right Bounding'));

    $properties['bottom'] = DataDefinition::create('float')
      ->setLabel(t('Bottom Bounding'));

    $properties['geohash'] = DataDefinition::create('string')
      ->setLabel(t('Geohash'));

    $properties['latlon'] = DataDefinition::create('string')
      ->setLabel(t('LatLong Pair'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Backend plugins need to define requirement/settings methods,
    // allow them to inject data here.
    $element = [];

    $backend_manager = \Drupal::service('plugin.manager.geofield_backend');

    $backends = $backend_manager->getDefinitions();
    $backend_options = [];

    foreach ($backends as $id => $backend) {
      $backend_options[$id] = $backend['admin_label'];
    }

    $element['backend'] = [
      '#type' => 'select',
      '#title' => $this->t('Storage Backend'),
      '#default_value' => $this->getSetting('backend'),
      '#options' => $backend_options,
      '#description' => $this->t("Select the Geospatial storage backend you would like to use to store geofield geometry data. If you don't know what this means, select 'Default Backend'."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    try {
      $value = $this->get('value')->getValue();
      if (!empty($value)) {
        /* @var \Drupal\geofield\GeoPHP\GeoPHPInterface $geo_php_wrapper */
        // Note: Geofield FieldType doesn't support Dependency Injection yet
        // (https://www.drupal.org/node/2053415).
        $geo_php_wrapper = \Drupal::service('geofield.geophp');
        /* @var \Geometry|null $geometry */
        $geometry = $geo_php_wrapper->load($value);
        return $geometry instanceof \Geometry ? $geometry->isEmpty() : TRUE;
      }
    }
    catch (\Exception $e) {
      watchdog_exception('geofield get value exception', $e);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values);
    $this->populateComputedValues();
  }

  /**
   * Populates computed variables.
   */
  protected function populateComputedValues() {

    /* @var \Geometry $geom */
    $geom = \Drupal::service('geofield.geophp')->load($this->value);

    if (!empty($geom)) {
      /* @var \Point $centroid */
      $centroid = $geom->getCentroid();
      $bounding = $geom->getBBox();

      $this->geo_type = $geom->geometryType();
      $this->lon = $centroid->getX();
      $this->lat = $centroid->getY();
      $this->left = $bounding['minx'];
      $this->top = $bounding['maxy'];
      $this->right = $bounding['maxx'];
      $this->bottom = $bounding['miny'];
      $this->geohash = substr($geom->out('geohash'), 0, GEOFIELD_GEOHASH_LENGTH);
      $this->latlon = $centroid->getY() . ',' . $centroid->getX();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCache() {
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $value = [
      'value' => \Drupal::service('geofield.wkt_generator')->WktGenerateGeometry(),
    ];
    return $value;
  }

}
