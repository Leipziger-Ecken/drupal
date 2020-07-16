<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\date_recur_modular\DateRecurModularUtilityTrait;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Date recur modular widget base.
 */
abstract class DateRecurModularWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  use DateRecurModularUtilityTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DateRecurModularWidgetBase.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $configFactory;
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
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * Determine the best suitable mode for a date recur field item.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of translatable modes keyed by mode.
   */
  protected function getModes(): array {
    return [];
  }

  /**
   * Determine the best suitable mode for a date recur field item.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item
   *   A date recur field item.
   *
   * @return string|null
   *   A mode.
   */
  protected function getMode(DateRecurItem $item): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$value) {
      // If each of start/end/time-zone contain invalid values, quit here.
      // Validation errors will show on form. Notably start and end day are
      // malformed arrays thanks to 'datetime' element.
      /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $start */
      $start = $value['start'] ?? NULL;
      /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $end */
      $end = $value['end'] ?? NULL;
      $timeZone = $value['time_zone'] ?? NULL;
      $mode = $value['mode'] ?? NULL;
      if (!$start instanceof DrupalDateTime || !$end instanceof DrupalDateTime || !is_string($timeZone) || !is_string($mode)) {
        $value = [];
      }
    }
    return $values;
  }

}
