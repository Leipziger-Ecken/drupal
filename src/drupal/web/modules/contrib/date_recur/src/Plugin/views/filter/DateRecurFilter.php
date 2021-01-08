<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\date_recur\DateRecurGranularityMap;
use Drupal\date_recur\DateRecurOccurrences;
use Drupal\date_recur\DateRecurUtility;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Date range/occurrence filter.
 *
 * Matches on entities having at least one occurrence matching the filter. Users
 * provide an input date of varying granularity, then occurrences are filtered
 * by whether the input date is between the start and end of occurrences..
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_recur_occurrences_filter")
 * @property \Drupal\views\Plugin\views\query\Sql $query
 */
class DateRecurFilter extends FilterPluginBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The smallest possible date given an input and granularity.
   *
   * @var \DateTime
   */
  protected $smallestDate;

  /**
   * The largest possible date given an input and granularity.
   *
   * @var \DateTime
   */
  protected $largestDate;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a DateRecurFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityFieldManagerInterface $entityFieldManager, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->entityFieldManager = $entityFieldManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Fixes when exposed filter turned on changes value to array. Seems like
   * property name is opposite of intention?
   */
  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    // The minimum date in \DATE_ISO8601 format.
    $options['value_min'] = ['default' => NULL];
    // The minimum date in \DATE_ISO8601 format.
    $options['value_max'] = ['default' => NULL];
    $options['value_granularity'] = ['default' => 'second'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $this->ensureMyTable();

    $dateRecurFieldName = $this->configuration['date recur field name'];
    $entityIdFieldName = $this->configuration['field base entity_id'];
    $fieldDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($this->configuration['entity_type']);
    $occurrenceTableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($fieldDefinitions[$dateRecurFieldName]);
    $storageTimezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $storageFormat = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;

    $subQuery = $this->database->select($occurrenceTableName, 'occurrences');
    $subQuery->addField('occurrences', 'entity_id');

    $largestDate = $this->largestDate;
    $largestDate->setTimezone($storageTimezone);
    $startFieldName = $dateRecurFieldName . '_value';
    $subQuery->condition($startFieldName, $largestDate->format($storageFormat), '<=');

    $smallestDate = $this->smallestDate;
    $smallestDate->setTimezone($storageTimezone);
    $endFieldName = $dateRecurFieldName . '_end_value';
    $subQuery->condition($endFieldName, $smallestDate->format($storageFormat), '>=');

    $subQuery->groupBy('entity_id');
    $this->query->addWhere(0, $this->tableAlias . '.' . $entityIdFieldName, $subQuery, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): array {
    $timezone = $this->currentUser->getTimeZone();
    $form['value'] = [
      '#title' => $this->t('Value'),
      '#description' => $this->t('A point in time in your local timezone.'),
      '#type' => 'textfield',
      // See ::defineOptions().
      '#default_value' => $this->value,
      '#element_validate' => [
        [static::class, 'validateValue'],
      ],
      // Pass along the plugin options so validator is aware.
      '#filter_plugin_options' => $this->options,
      '#filter_plugin_user_timezone' => !empty($timezone) ? $timezone : date_default_timezone_get(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['value_granularity'] = [
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('Select the level of granularity of occurrences.'),
      '#type' => 'select',
      '#options' => [
        'year' => $this->t('Absolute year'),
        'month' => $this->t('Absolute month'),
        'day' => $this->t('Absolute day'),
        'second' => $this->t('Datetime'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->options['value_granularity'],
    ];

    $minDefault = isset($this->options['value_min']) ? DrupalDateTime::createFromFormat(\DATE_ISO8601, $this->options['value_min']) : NULL;
    $form['value_minimum'] = [
      '#title' => $this->t('Minimum date'),
      '#description' => $this->t('Minimum date to use. If a larger granularity than <em>seconds</em> is chosen, this date will be rounded off. For example if this date and time is in September 2018 but the granularity is <em>year</em>, then the minimum year would be 2018.'),
      '#type' => 'datetime',
      '#default_value' => $minDefault,
    ];

    $maxDefault = isset($this->options['value_max']) ? DrupalDateTime::createFromFormat(\DATE_ISO8601, $this->options['value_max']) : NULL;
    $form['value_maximum'] = [
      '#title' => $this->t('Maximum date'),
      '#description' => $this->t('Maximum date to use. If a larger granularity than <em>seconds</em> is chosen, this date will be rounded off. For example if this date and time is in September 2018 but the granularity is <em>year</em>, then the maximum year would be 2018.'),
      '#type' => 'datetime',
      '#default_value' => $maxDefault,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::submitOptionsForm($form, $form_state);
    $this->options['value_granularity'] = $form_state->getValue(['options', 'value_granularity']);

    $utc = new \DateTimeZone('UTC');
    /* @var \Drupal\Core\Datetime\DrupalDateTime $min|null */
    $min = $form_state->getValue(['options', 'value_minimum']);
    if ($min) {
      $min->setTimezone($utc);
    }
    $this->options['value_min'] = $min ? $min->format(\DATE_ISO8601) : NULL;

    /* @var \Drupal\Core\Datetime\DrupalDateTime $max|null */
    $max = $form_state->getValue(['options', 'value_maximum']);
    if ($max) {
      $max->setTimezone($utc);
    }
    $this->options['value_max'] = $max ? $max->format(\DATE_ISO8601) : NULL;
  }

  /**
   * Form field validator.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateValue(array &$element, FormStateInterface $form_state): void {
    $elementValue = $element['#value'];
    if ($element['#required'] === FALSE && empty($elementValue)) {
      return;
    }

    /* @var $pluginOptions array */
    $pluginOptions = $element['#filter_plugin_options'];
    $granularity = $pluginOptions['value_granularity'];
    if (empty($granularity)) {
      throw new \LogicException('Granularity not set.');
    }

    /* @var string|null $optionValueMin */
    $optionValueMin = $pluginOptions['value_min'];
    $valueMin = isset($optionValueMin) ? \DateTime::createFromFormat(\DATE_ISO8601, $optionValueMin) : NULL;
    /* @var string|null $optionValueMax */
    $optionValueMax = $pluginOptions['value_max'];
    $valueMax = isset($optionValueMax) ? \DateTime::createFromFormat(\DATE_ISO8601, $optionValueMax) : NULL;

    $granularityFormatsMap = DateRecurGranularityMap::GRANULARITY_DATE_FORMATS;
    $format = $granularityFormatsMap[$granularity];

    $granularityRegexMap = DateRecurGranularityMap::GRANULARITY_EXPRESSIONS;
    $regex = $granularityRegexMap[$granularity];
    $value = $element['#value'];
    // Use the current users timezone.
    $timezone = new \DateTimeZone($element['#filter_plugin_user_timezone']);
    if (preg_match($regex, $value)) {
      // Validate value against minimum and maximums.
      if ($valueMin) {
        $largest = DateRecurUtility::createLargestDateFromInput($granularity, $value, $timezone);
        if ($largest < $valueMin) {
          $form_state->setError($element, (string) \t('Value is under minimum @minimum_as_granularity', [
            '@minimum_full' => $valueMin->format('r'),
            '@minimum_as_granularity' => $valueMin->format($format),
          ]));
        }
      }

      $smallest = DateRecurUtility::createSmallestDateFromInput($granularity, $value, $timezone);
      if ($valueMax) {
        if ($smallest > $valueMax) {
          $form_state->setError($element, (string) \t('Value is over maximum @maximum_as_granularity', [
            '@minimum_full' => $valueMax->format('r'),
            '@maximum_as_granularity' => $valueMax->format($format),
          ]));
        }
      }
    }
    // Input error.
    else {
      $now = new DrupalDateTime();
      $sample = $now->format($format);

      $granularityExpectedFormatMessages = DateRecurGranularityMap::granularityExpectedFormatMessages($sample);
      $form_state->setError($element, (string) \t('Value format is incorrect. Expected format: @example', [
        '@example' => $granularityExpectedFormatMessages[$granularity],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state): void {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    if ($form_state->isValueEmpty($identifier)) {
      return;
    }

    $input = $form_state->getValue($identifier);

    // Check if element validator created errors.
    $element = $form[$identifier];
    if (!$form_state->getError($element)) {
      $granularity = $this->options['value_granularity'];
      $timezone = new \DateTimeZone($element['#filter_plugin_user_timezone']);
      $this->smallestDate = DateRecurUtility::createSmallestDateFromInput($granularity, $input, $timezone);
      $this->largestDate = DateRecurUtility::createLargestDateFromInput($granularity, $input, $timezone);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): TranslatableMarkup {
    $granularityLabels = DateRecurGranularityMap::granularityLabels();
    $granularity = $this->options['value_granularity'];
    return $granularityLabels[$granularity];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();
    // Output of filter varies by timezone.
    $contexts[] = 'timezone';
    return $contexts;
  }

}
