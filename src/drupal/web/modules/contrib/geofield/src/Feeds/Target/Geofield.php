<?php

namespace Drupal\geofield\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a geofield field mapper.
 *
 * @FeedsTarget(
 *   id = "geofield_feeds_target",
 *   field_types = {"geofield"}
 * )
 */
class Geofield extends FieldTargetBase implements ContainerFactoryPluginInterface{

  /**
   * The Settings object or array.
   *
   * @var mixed
   */
  protected $settings;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a Geofield FeedsTarget object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MessengerInterface $messenger) {
    $this->targetDefinition = $configuration['target_definition'];
    $this->settings = $this->targetDefinition->getFieldDefinition()->getSettings();
    $this->messenger = $messenger;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('lat')
      ->addProperty('lon');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values) {
    $return = [];
    $coordinates = [];
    $coordinates_counter = 0;

    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);

        foreach ($columns as $key => $items) {
          foreach ($items as $item) {
            $coordinates[$coordinates_counter][$key][] = $item;
          }
          $coordinates_counter = 0;
        }

      }
      catch (EmptyFeedException $e) {
        $this->messenger->addError($e->getMessage());

        return FALSE;
      };

    }
    if (isset($coordinates)) {
      foreach ($coordinates as $coordinate) {
        $count_of_coordinates = count($coordinate['lat']);

        for ($i = 0; $i < $count_of_coordinates; $i++) {
          $return[]['value'] = "POINT (" . $coordinate['lon'][$i] . " " . $coordinate['lat'][$i] . ")";
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // Here is been preparing values for coordinates.
    foreach ($values as $column => $value) {
      $separated_coordinates = explode(" ", $value);
      $values[$column] = [];

      foreach ($separated_coordinates as $coordinate) {
        $values[$column][] = (float) $coordinate;
      }
    }

    // Latitude and Longitude should be a pair, if not throw EmptyFeedException.
    if (count($values['lat']) != count($values['lon'])) {
      throw new EmptyFeedException('Latitude and Longitude should be a pair. Change your file and import again.');
    }
  }

}
