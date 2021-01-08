<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\date_recur\DateRecurHelper;
use Drupal\date_recur\DateRecurHelperInterface;
use Drupal\date_recur\DateRecurNonRecurringHelper;
use Drupal\date_recur\DateRecurRruleMap;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Plugin\Field\DateRecurDateTimeComputed;
use Drupal\date_recur\Plugin\Field\DateRecurOccurrencesComputed;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'date_recur' field type.
 *
 * @FieldType(
 *   id = "date_recur",
 *   label = @Translation("Recurring dates field"),
 *   description = @Translation("Field for storing recurring dates."),
 *   default_widget = "date_recur_basic_widget",
 *   default_formatter = "date_recur_basic_formatter",
 *   list_class = "\Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList",
 *   constraints = {
 *     "DateRecurRrule" = {},
 *     "DateRecurRuleParts" = {},
 *   }
 * )
 *
 * @property \Drupal\Core\Datetime\DrupalDateTime|null $start_date
 * @property \Drupal\Core\Datetime\DrupalDateTime|null $end_date
 * @property string|null $timezone
 * @property string|null $rrule
 */
class DateRecurItem extends DateRangeItem {

  /**
   * Part used represent when all parts in a frequency are supported.
   */
  public const PART_SUPPORTS_ALL = '*';

  /**
   * Value for frequency setting: 'Disabled'.
   *
   * @internal will be made protected.
   */
  public const FREQUENCY_SETTINGS_DISABLED = 'disabled';

  /**
   * Value for frequency setting: 'All parts'.
   *
   * @internal will be made protected.
   */
  public const FREQUENCY_SETTINGS_PARTS_ALL = 'all-parts';

  /**
   * Value for frequency setting: 'Specify parts'.
   *
   * @internal will be made protected.
   */
  public const FREQUENCY_SETTINGS_PARTS_PARTIAL = 'some-parts';

  /**
   * The date recur helper.
   *
   * @var \Drupal\date_recur\DateRecurHelperInterface|null
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);

    /** @var \Drupal\Core\TypedData\DataDefinition $startDateProperty */
    $startDateProperty = $properties['start_date'];
    $startDateProperty->setClass(DateRecurDateTimeComputed::class);
    /** @var \Drupal\Core\TypedData\DataDefinition $endDateProperty */
    $endDateProperty = $properties['end_date'];
    $endDateProperty->setClass(DateRecurDateTimeComputed::class);

    $properties['rrule'] = DataDefinition::create('string')
      ->setLabel((string) new TranslatableMarkup('RRule'))
      ->setRequired(FALSE);
    $rruleMaxLength = $field_definition->getSetting('rrule_max_length');
    assert(empty($rruleMaxLength) || (is_numeric($rruleMaxLength) && $rruleMaxLength > 0));
    if (!empty($rruleMaxLength)) {
      $properties['rrule']->addConstraint('Length', ['max' => $rruleMaxLength]);
    }

    $properties['timezone'] = DataDefinition::create('string')
      ->setLabel((string) new TranslatableMarkup('Timezone'))
      ->setRequired(TRUE)
      ->addConstraint('DateRecurTimeZone');

    $properties['infinite'] = DataDefinition::create('boolean')
      ->setLabel((string) new TranslatableMarkup('Whether the RRule is an infinite rule. Derived value from RRULE.'))
      ->setRequired(FALSE);

    $properties['occurrences'] = ListDataDefinition::create('any')
      ->setLabel((string) new TranslatableMarkup('Occurrences'))
      ->setComputed(TRUE)
      ->setClass(DateRecurOccurrencesComputed::class);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['rrule'] = [
      'description' => 'The repeat rule.',
      'type' => 'text',
    ];
    $schema['columns']['timezone'] = [
      'description' => 'The timezone.',
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['columns']['infinite'] = [
      'description' => 'Whether the RRule is an infinite rule. Derived value from RRULE.',
      'type' => 'int',
      'size' => 'tiny',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'rrule_max_length' => 256,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      // @todo needs settings tests.
      'precreate' => 'P2Y',
      'parts' => [
        'all' => TRUE,
        'frequencies' => [],
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    assert(is_bool($has_data));
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['rrule_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum character length of RRULE'),
      '#description' => $this->t('Define the maximum characters a RRULE can contain.'),
      '#default_value' => $this->getSetting('rrule_max_length'),
      '#min' => 0,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    // Its not possible to locate the parent from FieldConfigEditForm.
    $elementParts = ['settings'];
    $element = parent::fieldSettingsForm($form, $form_state);

    // @todo Needs UI tests.
    $options = [];
    foreach (range(1, 5) as $i) {
      $options['P' . $i . 'Y'] = $this->formatPlural($i, '@year year', '@year years', ['@year' => $i]);
    }
    // @todo allow custom values.
    $element['precreate'] = [
      '#type' => 'select',
      '#title' => $this->t('Precreate occurrences'),
      '#description' => $this->t('For infinitely repeating dates, precreate occurrences for this amount of time in the views cache table.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('precreate'),
    ];

    $element['parts'] = [
      '#type' => 'container',
    ];
    $element['parts']['#after_build'][] = [get_class($this), 'partsAfterBuild'];

    $allPartsSettings = $this->getSetting('parts');
    $element['parts']['all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow all frequency and parts'),
      '#default_value' => $allPartsSettings['all'] ?? TRUE,
    ];
    $parents = array_merge($elementParts, ['parts', 'all']);
    // The form 'name' attribute of the 'all' parts checkbox above.
    $allPartsCheckboxName = $parents[0] . '[' . implode('][', array_slice($parents, 1)) . ']';

    $frequencyLabels = DateRecurRruleMap::frequencyLabels();
    $partLabels = DateRecurRruleMap::partLabels();

    $partsCheckboxes = [];
    foreach (DateRecurRruleMap::PARTS as $part) {
      $partsCheckboxes[$part] = [
        '#type' => 'checkbox',
        '#title' => $partLabels[$part],
      ];
    }
    $settingsOptions = [
      static::FREQUENCY_SETTINGS_DISABLED => $this->t('Disabled'),
      static::FREQUENCY_SETTINGS_PARTS_ALL => $this->t('All parts'),
      static::FREQUENCY_SETTINGS_PARTS_PARTIAL => $this->t('Specify parts'),
    ];

    // Table is a container so visibility states can be added.
    $element['parts']['table'] = [
      '#theme' => 'date_recur_settings_frequency_table',
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $allPartsCheckboxName . '"]' => ['checked' => FALSE],
        ],
      ],
    ];
    foreach (DateRecurRruleMap::FREQUENCIES as $frequency) {
      $row = [];
      $row['frequency']['#markup'] = $frequencyLabels[$frequency];

      $parents = array_merge($elementParts, [
        'parts',
        'table',
        $frequency,
        'setting',
      ]);
      // Constructs a name that looks like
      // settings[parts][table][MINUTELY][setting].
      $settingsCheckboxName = $parents[0] . '[' . implode('][', array_slice($parents, 1)) . ']';

      $enabledParts = $allPartsSettings['frequencies'][$frequency] ?? [];
      $defaultSetting = NULL;
      if (count($enabledParts) === 0) {
        $defaultSetting = static::FREQUENCY_SETTINGS_DISABLED;
      }
      elseif (in_array(static::PART_SUPPORTS_ALL, $enabledParts)) {
        $defaultSetting = static::FREQUENCY_SETTINGS_PARTS_ALL;
      }
      elseif (count($enabledParts) > 0) {
        $defaultSetting = static::FREQUENCY_SETTINGS_PARTS_PARTIAL;
      }

      $row['setting'] = [
        '#type' => 'radios',
        '#options' => $settingsOptions,
        '#required' => TRUE,
        '#default_value' => $defaultSetting,
      ];

      $row['parts'] = $partsCheckboxes;
      foreach ($row['parts'] as $part => &$partsCheckbox) {
        $partsCheckbox['#states']['visible'][] = [
          ':input[name="' . $settingsCheckboxName . '"]' => ['value' => static::FREQUENCY_SETTINGS_PARTS_PARTIAL],
        ];
        $partsCheckbox['#default_value'] = in_array($part, $enabledParts, TRUE);
      }

      $element['parts']['table'][$frequency] = $row;
    }

    $list = [];
    $partLabels = DateRecurRruleMap::partLabels();
    foreach (DateRecurRruleMap::partDescriptions() as $part => $partDescription) {
      $list[] = $this->t('<strong>@label:</strong> @description', [
        '@label' => $partLabels[$part],
        '@description' => $partDescription,
      ]);
    }
    $element['parts']['help']['#markup'] = '<ul><li>' . implode('</li><li>', $list) . '</li></ul></ul>';

    return $element;
  }

  /**
   * After build used to format of submitted values.
   *
   * FormBuilder has finished processing the input of children, now re-arrange
   * the values.
   *
   * @param array $element
   *   An associative array containing the structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The new structure of the element.
   */
  public static function partsAfterBuild(array $element, FormStateInterface $form_state): array {
    // Original parts container.
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Remove the original parts values so they dont get saved in same structure
    // as the form.
    NestedArray::unsetValue($form_state->getValues(), $element['#parents']);

    $parts = [];
    $parts['all'] = !empty($values['all']);
    $parts['frequencies'] = [];
    foreach ($values['table'] as $frequency => $row) {
      $enabledParts = array_keys(array_filter($row['parts']));
      if ($row['setting'] === static::FREQUENCY_SETTINGS_PARTS_ALL) {
        $enabledParts[] = static::PART_SUPPORTS_ALL;
      }
      elseif ($row['setting'] === static::FREQUENCY_SETTINGS_DISABLED) {
        $enabledParts = [];
      }
      // Sort in order so config always looks consistent.
      sort($enabledParts);
      $parts['frequencies'][$frequency] = $enabledParts;
    }

    // Set the new value.
    $form_state->setValue($element['#parents'], $parts);

    return $element;
  }

  /**
   * Get the date storage format of this field.
   *
   * @return string
   *   A date format string.
   */
  public function getDateStorageFormat(): string {
    // @todo tests
    return $this->getSetting('datetime_type') == static::DATETIME_TYPE_DATE ? static::DATE_STORAGE_FORMAT : static::DATETIME_STORAGE_FORMAT;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(): void {
    parent::preSave();
    try {
      $isInfinite = $this->getHelper()->isInfinite();
    }
    catch (DateRecurHelperArgumentException $e) {
      $isInfinite = FALSE;
    }
    $this->get('infinite')->setValue($isInfinite);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE): void {
    // Cast infinite to boolean on load.
    $values['infinite'] = !empty($values['infinite']);
    // All values are going to be overwritten atomically.
    $this->resetHelper();
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    if (in_array($property_name, ['value', 'end_value', 'rrule', 'timezone'])) {
      // Reset cached helper instance if values changed.
      $this->resetHelper();
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * Determine whether the field value is recurring/repeating.
   *
   * @return bool
   *   Whether the field value is recurring.
   */
  public function isRecurring(): bool {
    return !empty($this->rrule);
  }

  /**
   * Get the helper for this field item.
   *
   * Will always return a helper even if field value is non-recurring.
   *
   * @return \Drupal\date_recur\DateRecurHelperInterface
   *   The helper.
   *
   * @throws \Drupal\date_recur\Exception\DateRecurHelperArgumentException
   *   If a helper could not be created due to faulty field value.
   */
  public function getHelper(): DateRecurHelperInterface {
    if (isset($this->helper)) {
      return $this->helper;
    }

    try {
      $timeZoneString = $this->timezone;
      // If its not a string then cast it so a TypeError is not thrown. An empty
      // string will cause the exception to be thrown.
      $timeZone = new \DateTimeZone(is_string($timeZoneString) ? $timeZoneString : '');
    }
    catch (\Exception $exception) {
      throw new DateRecurHelperArgumentException('Invalid time zone');
    }

    $startDate = NULL;
    $startDateEnd = NULL;
    if ($this->start_date instanceof DrupalDateTime) {
      $startDate = $this->start_date->getPhpDateTime();
      $startDate->setTimezone($timeZone);
    }
    else {
      throw new DateRecurHelperArgumentException('Start date is required.');
    }
    if ($this->end_date instanceof DrupalDateTime) {
      $startDateEnd = $this->end_date->getPhpDateTime();
      $startDateEnd->setTimezone($timeZone);
    }
    $this->helper = $this->isRecurring() ?
      DateRecurHelper::create((string) $this->rrule, $startDate, $startDateEnd) :
      DateRecurNonRecurringHelper::createInstance('', $startDate, $startDateEnd);
    return $this->helper;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $start_value = $this->get('value')->getValue();
    $end_value = $this->get('end_value')->getValue();
    return (
      // Use OR operator instead of AND from parent. See
      // https://www.drupal.org/project/drupal/issues/3025812
      ($start_value === NULL || $start_value === '') ||
      ($end_value === NULL || $end_value === '') ||
      empty($this->get('timezone')->getValue())
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $values = parent::generateSampleValue($field_definition);

    $timeZoneList = timezone_identifiers_list();
    $values['timezone'] = $timeZoneList[array_rand($timeZoneList)];
    $values['rrule'] = 'FREQ=DAILY;COUNT=' . rand(2, 10);
    $values['infinite'] = FALSE;

    return $values;
  }

  /**
   * Resets helper value since source values changed.
   */
  public function resetHelper(): void {
    $this->helper = NULL;
  }

}
