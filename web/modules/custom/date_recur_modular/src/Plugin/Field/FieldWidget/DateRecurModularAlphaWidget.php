<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\DateRecurRuleInterface;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\date_recur_modular\DateRecurModularWidgetOptions;

/**
 * Date recur alpha widget.
 *
 * There is no particular reason for 'alpha' naming, other than it is the first.
 *
 * This is a widget built with Drupal states in combination with light sprinkle
 * of CSS.
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
 * Frequencies and parts are designed to be inaccessible or temporarily
 * invisible or if field level frequency/part configuration dictate it.
 *
 * @FieldWidget(
 *   id = "date_recur_modular_alpha",
 *   label = @Translation("Modular: Alpha"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurModularAlphaWidget extends DateRecurModularWidgetBase {

  use DateRecurModularWidgetFieldsTrait;

  protected const MODE_ONCE = 'once';

  protected const MODE_MULTIDAY = 'multiday';

  protected const MODE_WEEKLY = 'weekly';

  protected const MODE_FORTNIGHTLY = 'fortnightly';

  protected const MODE_MONTHLY = 'monthly';

  /**
   * Part grid for this list.
   *
   * @var \Drupal\date_recur\DateRecurPartGrid
   */
  protected $partGrid;

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
    $elementParents = array_merge($element['#field_parents'], [$this->fieldDefinition->getName(), $delta]);
    $element['#element_validate'][] = [static::class, 'validateModularWidget'];
    $element['#after_build'][] = [static::class, 'afterBuildModularWidget'];
    $element['#theme'] = 'date_recur_modular_alpha_widget';
    $element['#theme_wrappers'][] = 'form_element';

    $item = $items[$delta];

    $grid = $items->getPartGrid();
    $rule = $this->getRule($item);
    $parts = $rule ? $rule->getParts() : [];
    $count = $parts['COUNT'] ?? NULL;
    $timeZone = $this->getDefaultTimeZone($item);
    $endsDate = NULL;
    try {
      $until = $parts['UNTIL'] ?? NULL;
      if (is_string($until)) {
        $endsDate = new \DateTime($until);
      }
      elseif ($until instanceof \DateTimeInterface) {
        $endsDate = $until;
      }
      if ($endsDate) {
        // UNTIL is _usually_ in UTC, adjust it to the field time zone.
        $endsDate->setTimezone(new \DateTimeZone($timeZone));
      }
    }
    catch (\Exception $e) {
    }

    $fieldModes = $this->getFieldModes($grid);

    $element['start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Starts on'),
      '#title_display' => 'invisible',
      '#default_value' => $item->start_date,
      // \Drupal\Core\Datetime\Element\Datetime::valueCallback tries to change
      // the time zone to current users timezone if not set, Set the timezone
      // here so the value doesn't change.
      '#date_timezone' => $timeZone,
    ];
    $element['end'] = [
      '#title' => $this->t('Ends on'),
      '#title_display' => 'invisible',
      '#type' => 'datetime',
      '#default_value' => $item->end_date,
      '#date_timezone' => $timeZone,
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
      // Some part elements also need to check access, as when states are
      // applied if there are no conditions then the field is always visible.
      '#access' => count($fieldModes['daily_count'] ?? []) > 0,
    ];
    $element['daily_count']['#states'] = $this->getVisibilityStates($element, $fieldModes['daily_count'] ?? []);

    $element['weekdays'] = $this->getFieldByDay($rule);
    $element['weekdays']['#states'] = $this->getVisibilityStates($element, $fieldModes['weekdays'] ?? []);
    $element['weekdays']['#title_display'] = 'invisible';

    $element['ordinals'] = $this->getFieldMonthlyByDayOrdinals($element, $rule);
    $element['ordinals']['#states'] = $this->getVisibilityStates($element, $fieldModes['ordinals'] ?? []);
    $element['ordinals']['#title_display'] = 'invisible';

    $element['time_zone'] = $this->getFieldTimeZone($timeZone);

    $endsModeDefault =
      $endsDate ? DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE :
      ($count > 0 ? DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES : DateRecurModularWidgetOptions::ENDS_MODE_INFINITE);
    $element['ends_mode'] = $this->getFieldEndsMode();
    $element['ends_mode']['#states'] = $this->getVisibilityStates($element, $fieldModes['ends_mode'] ?? []);
    $element['ends_mode']['#title_display'] = 'before';
    $element['ends_mode']['#default_value'] = $endsModeDefault;
    // Hide or show 'On date' / 'number of occurrences' checkboxes depending on
    // selected mode.
    $element['ends_mode'][DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES]['#states'] = $this->getVisibilityStates($element, $fieldModes['ends_count'] ?? []);
    $element['ends_mode'][DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE]['#states'] = $this->getVisibilityStates($element, $fieldModes['ends_date'] ?? []);

    $element['ends_count'] = [
      '#type' => 'number',
      '#title' => $this->t('End after number of occurrences'),
      '#title_display' => 'invisible',
      '#field_prefix' => $this->t('after'),
      '#field_suffix' => $this->t('occurrences'),
      '#default_value' => $count ?? 1,
      '#min' => 1,
      '#access' => count($fieldModes['ends_count'] ?? []) > 0,
    ];
    $nameMode = $this->getName($element, ['mode']);
    $nameEndsMode = $this->getName($element, ['ends_mode']);
    $element['ends_count']['#states']['visible'] = [];
    foreach ($fieldModes['ends_count'] ?? [] as $mode) {
      $element['ends_count']['#states']['visible'][] = [
        ':input[name="' . $nameMode . '"]' => ['value' => $mode],
        ':input[name="' . $nameEndsMode . '"]' => ['value' => DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES],
      ];
    }

    // States dont yet work on date time so put it in a container.
    // @see https://www.drupal.org/project/drupal/issues/2419131
    $element['ends_date'] = [
      '#type' => 'container',
    ];
    $element['ends_date']['ends_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End before this date'),
      '#title_display' => 'invisible',
      '#description' => $this->t('No occurrences can begin after this date.'),
      '#default_value' => $endsDate ? DrupalDateTime::createFromDateTime($endsDate) : NULL,
      // Fix values tree thanks to state+container hack.
      '#parents' => array_merge($elementParents, ['ends_date']),
      // \Drupal\Core\Datetime\Element\Datetime::valueCallback tries to change
      // the time zone to current users timezone if not set, Set the timezone
      // here so the value doesn't change.
      '#date_timezone' => $timeZone,
    ];

    $element['ends_date']['#states']['visible'] = [];
    foreach ($fieldModes['ends_date'] ?? [] as $mode) {
      $element['ends_date']['#states']['visible'][] = [
        ':input[name="' . $nameMode . '"]' => ['value' => $mode],
        ':input[name="' . $nameEndsMode . '"]' => ['value' => DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE],
      ];
    }

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
    // Each of these values can be array if input was invalid. E.g date or time
    // not provided.
    /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $start */
    $start = $form_state->getValue(array_merge($element['#parents'], ['start']));
    /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $end */
    $end = $form_state->getValue(array_merge($element['#parents'], ['end']));
    /** @var string|null $timeZone */
    $timeZone = $form_state->getValue(array_merge($element['#parents'], ['time_zone']));

    if ($start && !$timeZone) {
      $form_state->setError($element['start'], \t('Time zone must be set if start date is set.'));
    }
    if ($end && !$timeZone) {
      $form_state->setError($element['end'], \t('Time zone must be set if end date is set.'));
    }
    if (($start instanceof DrupalDateTime || $end instanceof DrupalDateTime) && (!$start instanceof DrupalDateTime || !$end instanceof DrupalDateTime)) {
      $form_state->setError($element, \t('Start date and end date must be provided.'));
    }

    // Recreate datetime object with exactly the same date and time but
    // different timezone.
    $zoneLess = 'Y-m-d H:i:s';
    $timeZoneObj = new \DateTimeZone($timeZone);
    if ($start instanceof DrupalDateTime && $timeZone) {
      $start = DrupalDateTime::createFromFormat($zoneLess, $start->format($zoneLess), $timeZoneObj);
      $form_state->setValueForElement($element['start'], $start);
    }
    if ($end instanceof DrupalDateTime && $timeZone) {
      $end = DrupalDateTime::createFromFormat($zoneLess, $end->format($zoneLess), $timeZoneObj);
      $form_state->setValueForElement($element['end'], $end);
    }
    /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $endsDate */
    $endsDate = $form_state->getValue(array_merge($element['#parents'], ['ends_date']));
    if ($endsDate instanceof DrupalDateTime && $timeZone) {
      $endsDate = DrupalDateTime::createFromFormat($zoneLess, $endsDate->format($zoneLess), $timeZoneObj);
      $form_state->setValueForElement($element['ends_date'], $endsDate);
    }
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
    $values = parent::massageFormValues($values, $form, $form_state);
    $dateStorageFormat = $this->fieldDefinition->getSetting('datetime_type') == DateRecurItem::DATETIME_TYPE_DATE ? DateRecurItem::DATE_STORAGE_FORMAT : DateRecurItem::DATETIME_STORAGE_FORMAT;
    $dateStorageTimeZone = new \DateTimezone(DateRecurItem::STORAGE_TIMEZONE);
    $grid = $this->partGrid;

    $returnValues = [];
    foreach ($values as $delta => $value) {
      // Call to parent invalidates and empties individual values.
      if (empty($value)) {
        continue;
      }

      $item = [];

      $start = $value['start'] ?? NULL;
      assert(!isset($start) || $start instanceof DrupalDateTime);
      $end = $value['end'] ?? NULL;
      assert(!isset($end) || $end instanceof DrupalDateTime);
      $timeZone = $value['time_zone'];
      assert(is_string($timeZone));
      $mode = $value['mode'] ?? NULL;
      $endsMode = $value['ends_mode'] ?? NULL;
      /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $endsDate */
      $endsDate = $value['ends_date'] ?? NULL;

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

      // Ends mode.
      if ($endsMode === DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES && $mode !== static::MODE_MULTIDAY) {
        $rule['COUNT'] = (int) $value['ends_count'];
      }
      elseif ($endsMode === DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE && $endsDate instanceof DrupalDateTime) {
        $endsDateUtcAdjusted = (clone $endsDate)
          ->setTimezone(new \DateTimeZone('UTC'));
        $rule['UNTIL'] = $endsDateUtcAdjusted->format('Ymd\THis\Z');
      }

      if (isset($rule['FREQ'])) {
        $rule = array_filter($rule);
        $item['rrule'] = $this->buildRruleString($rule, $grid);
      }

      $returnValues[] = $item;
    }

    return $returnValues;
  }

  /**
   * Ordinals (BYSETPOS).
   *
   * Designed for MONTHLY combined with BYDAY.
   *
   * @param array $element
   *   The currently built element.
   * @param \Drupal\date_recur\DateRecurRuleInterface|null $rule
   *   Optional rule for which default value is derived.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldMonthlyByDayOrdinals(array $element, ?DateRecurRuleInterface $rule): array {
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
   * Get field modes for generating #states arrays.
   *
   * Determines whether some fields should be visible.
   *
   * @param \Drupal\date_recur\DateRecurPartGrid $grid
   *   A part grid object.
   *
   * @return array
   *   Field modes.
   */
  protected function getFieldModes(DateRecurPartGrid $grid): array {
    $fieldModes = [];

    if ($grid->isPartAllowed('DAILY', 'COUNT')) {
      $fieldModes['daily_count'][] = static::MODE_MULTIDAY;
    }

    if ($grid->isPartAllowed('WEEKLY', 'BYDAY')) {
      $fieldModes['weekdays'][] = static::MODE_WEEKLY;
      $fieldModes['weekdays'][] = static::MODE_FORTNIGHTLY;
    }
    $count = $grid->isPartAllowed('WEEKLY', 'COUNT');
    $until = $grid->isPartAllowed('WEEKLY', 'UNTIL');
    if ($count || $until) {
      $fieldModes['ends_mode'][] = static::MODE_WEEKLY;
      $fieldModes['ends_mode'][] = static::MODE_FORTNIGHTLY;
      if ($count) {
        $fieldModes['ends_count'][] = static::MODE_WEEKLY;
        $fieldModes['ends_count'][] = static::MODE_FORTNIGHTLY;
      }
      if ($until) {
        $fieldModes['ends_date'][] = static::MODE_WEEKLY;
        $fieldModes['ends_date'][] = static::MODE_FORTNIGHTLY;
      }
    }

    if ($grid->isPartAllowed('MONTHLY', 'BYSETPOS')) {
      $fieldModes['ordinals'][] = static::MODE_MONTHLY;
    }
    if ($grid->isPartAllowed('MONTHLY', 'BYDAY')) {
      $fieldModes['weekdays'][] = static::MODE_MONTHLY;
    }
    $count = $grid->isPartAllowed('MONTHLY', 'COUNT');
    $until = $grid->isPartAllowed('MONTHLY', 'UNTIL');
    if ($count || $until) {
      $fieldModes['ends_mode'][] = static::MODE_MONTHLY;
      if ($count) {
        $fieldModes['ends_count'][] = static::MODE_MONTHLY;
      }
      if ($until) {
        $fieldModes['ends_date'][] = static::MODE_MONTHLY;
      }
    }

    return $fieldModes;
  }

}
