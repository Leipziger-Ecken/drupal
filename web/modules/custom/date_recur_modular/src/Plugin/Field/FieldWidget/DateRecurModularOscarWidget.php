<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurRuleInterface;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Date recur opening hours widget.
 *
 * The codename 'oscar' is used because it was designed for 'Opening hours'
 *
 * This is a widget built with Drupal states in combination with light sprinkle
 * of CSS.
 *
 * Date range always occurs on the same day, the range cannot spread across
 * multiple days. An all-day option is provided.
 *
 * Frequencies and parts are designed to be inaccessible or temporarily
 * invisible or if field level frequency/part configuration dictate it.
 *
 * It supports the following modes:
 *  - Non recurring.
 *  - Multiday.
 *  - Weekly:
 *    - hard coded interval of 1. Or 2 if fortnightly is chosen.
 *    - with optional expansion to multiple weekdays.
 *    - with optional occurrence limitation by date or count.
 *  - Monthly:
 *    - hard coded interval of 1.
 *    - with optional expansion to multiple weekdays.
 *    - with optional limiter on ordinal.
 *    - with optional occurrence limitation by date or count.
 *
 * @FieldWidget(
 *   id = "date_recur_modular_oscar",
 *   label = @Translation("Modular: Oscar"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurModularOscarWidget extends DateRecurModularWidgetBase {

  use DateRecurModularWidgetFieldsTrait;

  protected const MODE_ONCE = 'once';

  protected const MODE_MULTIDAY = 'multiday';

  protected const MODE_WEEKLY = 'weekly';

  protected const MODE_FORTNIGHTLY = 'fortnightly';

  protected const MODE_MONTHLY = 'monthly';

  protected const IS_ALL_DAY_ALL = 'all-day';

  protected const IS_ALL_DAY_PARTIAL = 'partial';

  protected const HTML_TIME_FORMAT = 'H:i:s';

  /**
   * Part grid for this list.
   *
   * @var \Drupal\date_recur\DateRecurPartGrid
   */
  protected $partGrid;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'all_day_toggle' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $summary[] = $this->isAllDayToggleEnabled() ?
      $this->t('All day toggle: enabled') :
      $this->t('All day toggle: disabled');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['all_day_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable all-day toggle'),
      '#description' => $this->t('Whether to enable the all-day/between toggle.'),
      '#default_value' => $this->getSetting('all_day_toggle'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModes(): array {
    return [
      static::MODE_ONCE => $this->t('Once'),
      static::MODE_MULTIDAY => $this->t('Multiple days'),
      static::MODE_WEEKLY => $this->t('Weekly'),
      static::MODE_FORTNIGHTLY => $this->t('Fortnightly'),
      static::MODE_MONTHLY => $this->t('Monthly'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMode(DateRecurItem $item): ?string {
    try {
      $helper = $item->getHelper();
    }
    catch (DateRecurHelperArgumentException $e) {
      return NULL;
    }

    $rules = $helper->getRules();
    $rule = reset($rules);
    if (FALSE === $rule) {
      // This widget supports one RRULE per field value.
      return NULL;
    }

    $frequency = $rule->getFrequency();
    $parts = $rule->getParts();

    if ('DAILY' === $frequency) {
      /** @var int|null $count */
      $count = $parts['COUNT'] ?? NULL;
      return $count && $count > 1 ? static::MODE_MULTIDAY : static::MODE_ONCE;
    }
    elseif ('WEEKLY' === $frequency) {
      /** @var int|null $interval */
      $interval = $parts['INTERVAL'] ?? NULL;
      return [1 => static::MODE_WEEKLY, 2 => static::MODE_FORTNIGHTLY][$interval] ?? NULL;
    }
    elseif ('MONTHLY' === $frequency) {
      return static::MODE_MONTHLY;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList|\Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem[] $items */
    $element['#element_validate'][] = [static::class, 'validateModularWidget'];
    $element['#after_build'][] = [static::class, 'afterBuildModularWidget'];
    $element['#theme'] = 'date_recur_modular_oscar_widget';

    $item = $items[$delta];

    $grid = $items->getPartGrid();
    $rule = $this->getRule($item);
    $parts = $rule ? $rule->getParts() : [];
    $count = $parts['COUNT'] ?? NULL;

    $fieldModes = [];
    if ($grid->isFrequencyAllowed('DAILY')) {
      if ($grid->isPartAllowed('DAILY', 'BYDAY')) {
        $fieldModes['daily_count'][] = static::MODE_MULTIDAY;
      }
    }
    if ($grid->isFrequencyAllowed('WEEKLY')) {
      if ($grid->isPartAllowed('WEEKLY', 'BYDAY')) {
        $fieldModes['weekdays'][] = static::MODE_WEEKLY;
        $fieldModes['weekdays'][] = static::MODE_FORTNIGHTLY;
      }
    }
    if ($grid->isFrequencyAllowed('MONTHLY')) {
      if ($grid->isPartAllowed('MONTHLY', 'BYSETPOS')) {
        $fieldModes['ordinals'][] = static::MODE_MONTHLY;
      }
      if ($grid->isPartAllowed('WEEKLY', 'BYDAY')) {
        $fieldModes['weekdays'][] = static::MODE_MONTHLY;
      }
    }

    $element['day_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Start day'),
      '#default_value' => $item->start_date instanceof DrupalDateTime ? $item->start_date->format('Y-m-d') : NULL,
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
    ];

    $isAllDay = FALSE;
    if ($item->start_date && $item->end_date) {
      $isAllDay =
        // Calendar day is same.
        ($item->start_date->format('Y-m-d') === $item->end_date->format('Y-m-d')) &&
        // Ignore seconds when checking whether time is all day.
        (substr($item->start_date->format('H:i'), 0, 5) === '00:00') &&
        (substr($item->end_date->format('H:i'), 0, 5) === '23:59');
    }

    $element['is_all_day'] = [
      '#type' => 'radios',
      // Dont add title, else preRenderCompositeFormElement uses fieldset, which
      // browsers wont let flex.
      '#default_value' => $isAllDay ? static::IS_ALL_DAY_ALL : static::IS_ALL_DAY_PARTIAL,
      '#options' => [
        static::IS_ALL_DAY_ALL => $this->t('All day'),
        static::IS_ALL_DAY_PARTIAL => $this->t('Between time'),
      ],
      '#access' => $this->isAllDayToggleEnabled(),
    ];

    $element['times'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="' . $this->getName($element, ['is_all_day']) . '"]' => ['value' => static::IS_ALL_DAY_ALL],
        ],
      ],
    ];

    $element['times']['time_start'] = [
      '#type' => 'date',
      '#attributes' => [
        'type' => 'time',
        // Must specify increment else browsers default to 60, which omits
        // seconds. Our validation expects seconds.
        'step' => 1,
      ],
      '#title' => $this->t('Time'),
      '#default_value' => $item->start_date instanceof DrupalDateTime ? $item->start_date->format(static::HTML_TIME_FORMAT) : NULL,
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      // Must specify increment else browsers default to 60, which omits
      // seconds. Our validation expects seconds.
      '#date_increment' => 1,
    ];

    $element['times']['time_end'] = [
      '#title' => $this->t('Ending time'),
      '#title_display' => 'invisible',
      '#type' => 'date',
      '#attributes' => [
        'type' => 'time',
        // Must specify increment else browsers default to 60, which omits
        // seconds. Our validation expects seconds.
        'step' => 1,
      ],
      '#default_value' => $item->end_date instanceof DrupalDateTime ? $item->end_date->format(static::HTML_TIME_FORMAT) : NULL,
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      '#prefix' => $this->t('to'),
    ];

    $element['mode'] = $this->getFieldMode($item);
    $element['mode']['#title_display'] = 'invisible';

    $element['daily_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Days'),
      '#title_display' => 'invisible',
      '#field_suffix' => $this->t('days'),
      '#default_value' => $count ?? 1,
      '#min' => 1,
    ];
    $element['daily_count']['#states'] = $this->getVisibilityStates($element, $fieldModes['daily_count'] ?? []);

    $element['weekdays'] = $this->getFieldByDay($rule);
    $element['weekdays']['#states'] = $this->getVisibilityStates($element, $fieldModes['weekdays'] ?? []);
    $element['weekdays']['#title_display'] = 'invisible';
    foreach ($element['weekdays']['#options'] as $key => &$value) {
      // Change all 'checkbox' elements created. These sub elements are merged
      // into by the 'checkboxes' element.
      $element['weekdays'][$key]['#title_display'] = 'before';
      $element['weekdays'][$key]['#attributes']['title'] = $value;
      // Change the label to a short letter of weekday.
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $value */
      $value = substr((string) $value, 0, 1);
    }

    $element['ordinals'] = $this->getFieldMonthlyByDayOrdinals($element, $rule);
    $element['ordinals']['#states'] = $this->getVisibilityStates($element, $fieldModes['ordinals'] ?? []);
    $element['ordinals']['#title_display'] = 'invisible';

    $element['time_zone'] = $this->getFieldTimeZone($this->getDefaultTimeZone($item));
    $element['time_zone']['#access'] = FALSE;

    return $element;
  }

  /**
   * Validates the widget.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateModularWidget(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    /** @var string|null $dayStartString */
    $dayStartString = $form_state->getValue(array_merge($element['#parents'], ['day_start']));
    $timeStartString = $form_state->getValue(array_merge($element['#parents'], ['times', 'time_start']), '');
    $timeEndString = $form_state->getValue(array_merge($element['#parents'], ['times', 'time_end']), '');
    $hasAnyDateTimeInput = !empty($dayStartString) || !empty($timeStartString) || !empty($timeEndString);

    if (!$hasAnyDateTimeInput) {
      // Skip if all of date and times are empty.
      $form_state->setValueForElement($element['day_start'], NULL);
      $form_state->setValueForElement($element['times']['time_start'], NULL);
      $form_state->setValueForElement($element['times']['time_end'], NULL);
      return;
    }

    /** @var string|null $timeZone */
    $timeZone = $form_state->getValue(array_merge($element['#parents'], ['time_zone']));
    if (empty($timeZone)) {
      $form_state->setError($element['end'], \t('Time zone must be set.'));
      return;
    }

    // Create base day object.
    try {
      $baseDay = DrupalDateTime::createFromFormat('Y-m-d', $dayStartString, $timeZone);
      $baseDay->setTime(0, 0, 0);
      $baseDayParts = explode('-', $baseDay->format('Y-n-j'));
    }
    catch (\Exception $e) {
      $form_state->setError($element['day_start'], \t('Invalid start day.'));
      return;
    }

    $isAllDay = $form_state->getValue(array_merge($element['#parents'], ['is_all_day'])) === static::IS_ALL_DAY_ALL;
    if ($isAllDay) {
      $startDate = (clone $baseDay)->setTime(0, 0, 0);
      $endDate = (clone $baseDay)->setTime(23, 59, 59);
    }
    else {
      // When time is POST'ed with 00 seconds, the entire part is missing.
      // Restore it here.
      $fixTime = function (string $time): string {
        // See also \Drupal\Core\Datetime\Element\Datetime::valueCallback.
        // Seconds will be omitted in a post in case there's no entry.
        if (!empty($time) && strlen($time) == 5) {
          $time .= ':00';
        }
        return $time;
      };

      try {
        $timeStartString = $fixTime($timeStartString);
        $startDate = DrupalDateTime::createFromFormat(static::HTML_TIME_FORMAT, $timeStartString, $timeZone);
        $startDate->setDate(...$baseDayParts);
      }
      catch (\Exception $e) {
        $form_state->setValueForElement($element['times']['time_start'], NULL);
        $form_state->setError($element['times']['time_start'], \t('Invalid start time.'));
        return;
      }

      try {
        $timeEndString = $fixTime($timeEndString);
        $endDate = DrupalDateTime::createFromFormat(static::HTML_TIME_FORMAT, $timeEndString, $timeZone);
        $endDate->setDate(...$baseDayParts);
      }
      catch (\Exception $e) {
        $form_state->setValueForElement($element['times']['time_end'], NULL);
        $form_state->setError($element['times']['time_end'], \t('Invalid end time.'));
        return;
      }
    }

    if ($endDate->getPhpDateTime() < $startDate->getPhpDateTime()) {
      $form_state->setError($element['times']['time_end'], 'End time must be after start time.');
    }

    $form_state->setValueForElement($element['times']['time_start'], $startDate);
    $form_state->setValueForElement($element['times']['time_end'], $endDate);
  }

  /**
   * After build callback for the widget.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element.
   */
  public static function afterBuildModularWidget(array $element, FormStateInterface $form_state) {
    // Wait until ID is created, and after
    // \Drupal\Core\Render\Element\Checkboxes::processCheckboxes is run so
    // states are not replicated to children.
    $weekdaysId = $element['weekdays']['#id'];
    $element['ordinals']['#states']['visible'][0]['#' . $weekdaysId . ' input[type="checkbox"]'] = ['checked' => TRUE];

    // Add container classes to compact checkboxes.
    $element['weekdays']['#attributes']['class'][] = 'container-inline';
    $element['ordinals']['#attributes']['class'][] = 'container-inline';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $items */
    $this->partGrid = $items->getPartGrid();
    parent::extractFormValues(...func_get_args());
    unset($this->partGrid);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = array_map(function (array $value): array {
      // If each of start/end/timezone/zone contain invalid values, quit here.
      // Validation errors will show on form. Notably start and end day are
      // malformed arrays thanks to 'datetime' element.
      /** @var \Drupal\Core\Datetime\DrupalDateTime|null $start */
      $start = $value['times']['time_start'] ?? NULL;
      /** @var \Drupal\Core\Datetime\DrupalDateTime|null $end */
      $end = $value['times']['time_end'] ?? NULL;
      $timeZone = $value['time_zone'] ?? NULL;
      $mode = $value['mode'] ?? NULL;
      if (!$start instanceof DrupalDateTime || !$end instanceof DrupalDateTime || !is_string($timeZone) || !is_string($mode)) {
        return [];
      }
      return $value;
    }, $values);

    $dateStorageFormat = $this->fieldDefinition->getSetting('datetime_type') == DateRecurItem::DATETIME_TYPE_DATE ? DateRecurItem::DATE_STORAGE_FORMAT : DateRecurItem::DATETIME_STORAGE_FORMAT;
    $dateStorageTimeZone = new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $grid = $this->partGrid;

    $returnValues = [];
    foreach ($values as $delta => $value) {
      $returnValues[$delta] = [];

      // Value may have been emptied by start/end/tz/mode validation above.
      if (empty($value)) {
        continue;
      }

      $item = [];

      $start = $value['times']['time_start'] ?? NULL;
      assert(!isset($start) || $start instanceof DrupalDateTime);
      $end = $value['times']['time_end'] ?? NULL;
      assert(!isset($end) || $end instanceof DrupalDateTime);
      $timeZone = $value['time_zone'] ?? NULL;
      $mode = $value['mode'] ?? NULL;

      // Adjust the date for storage.
      $start->setTimezone($dateStorageTimeZone);
      $item['value'] = $start->format($dateStorageFormat);
      $end->setTimezone($dateStorageTimeZone);
      $item['end_value'] = $end->format($dateStorageFormat);
      $item['timezone'] = $timeZone;

      $weekDays = array_values(array_filter($value['weekdays']));
      $byDayStr = implode(',', $weekDays);

      $rule = [];
      if ($mode === static::MODE_MULTIDAY) {
        $rule['FREQ'] = 'DAILY';
        $rule['INTERVAL'] = 1;
        $rule['COUNT'] = $value['daily_count'];
      }
      elseif ($mode === static::MODE_WEEKLY) {
        $rule['FREQ'] = 'WEEKLY';
        $rule['INTERVAL'] = 1;
        $rule['BYDAY'] = $byDayStr;
      }
      elseif ($mode === static::MODE_FORTNIGHTLY) {
        $rule['FREQ'] = 'WEEKLY';
        $rule['INTERVAL'] = 2;
        $rule['BYDAY'] = $byDayStr;
      }
      elseif ($mode === static::MODE_MONTHLY) {
        $rule['FREQ'] = 'MONTHLY';
        $rule['INTERVAL'] = 1;
        $rule['BYDAY'] = $byDayStr;

        // Funge ordinals appropriately.
        $ordinalCheckboxes = array_filter($value['ordinals']);
        $ordinals = [];
        if (count($ordinalCheckboxes) && count($weekDays)) {
          $weekdayCount = count($weekDays);

          // Expand simplified ordinals into spec compliant BYSETPOS ordinals.
          foreach ($ordinalCheckboxes as $ordinal) {
            $end = $ordinal * $weekdayCount;
            $diff = ($weekdayCount - 1);
            $start = ($end > 0) ? $end - $diff : $end + $diff;
            $range = range($start, $end);
            array_push($ordinals, ...$range);
          }

          // Order doesn't matter but simplifies testing.
          sort($ordinals);
          $rule['BYSETPOS'] = implode(',', $ordinals);
        }
      }

      if (isset($rule['FREQ'])) {
        $rule = array_filter($rule);
        $item['rrule'] = $this->buildRruleString($rule, $grid);
      }

      $returnValues[$delta] = $item;
    }

    return $returnValues;
  }

  /**
   * Ordinals (BYSETPOS).
   *
   * Designed for MONTHLY combined with BYDAY.
   */
  protected function getFieldMonthlyByDayOrdinals($element, ?DateRecurRuleInterface $rule): array {
    $parts = $rule ? $rule->getParts() : [];

    $ordinals = [];
    $bySetPos = !empty($parts['BYSETPOS']) ? explode(',', $parts['BYSETPOS']) : [];
    if (count($bySetPos) > 0) {
      $weekdayCount = count($element['weekdays']['#default_value']);
      sort($bySetPos);

      // Collapse all ordinals into simplified ordinals.
      $chunks = array_chunk($bySetPos, $weekdayCount);
      foreach ($chunks as $chunk) {
        $first = reset($chunk);
        $end = ($first < 0) ? min($chunk) : max($chunk);
        $ordinals[] = $end / $weekdayCount;
      }
    }

    return [
      '#type' => 'checkboxes',
      '#title' => $this->t('Ordinals'),
      '#options' => [
        1 => $this->t('First'),
        2 => $this->t('Second'),
        3 => $this->t('Third'),
        4 => $this->t('Fourth'),
        5 => $this->t('Fifth'),
        -1 => $this->t('Last'),
        -2 => $this->t('2nd to last'),
      ],
      '#default_value' => $ordinals,
    ];
  }

  /**
   * Whether all day toggle is enabled.
   *
   * @return bool
   *   Whether all day toggle is enabled.
   */
  protected function isAllDayToggleEnabled(): bool {
    return !empty($this->getSetting('all_day_toggle'));
  }

}
