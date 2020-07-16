<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateHelper;
use Drupal\date_recur\DateRecurRuleInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Trait containing convenience methods for generating whole form fields.
 */
trait DateRecurModularWidgetFieldsTrait {

  /**
   * Get a time zone element.
   *
   * @param string|null $timeZone
   *   Optional default time zone for which default value is derived.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldTimeZone(?string $timeZone): array {
    // Saved values (should) always have a time zone.
    $zones = $this->getTimeZoneOptions();
    return [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#default_value' => $timeZone,
      '#options' => $zones,
    ];
  }

  /**
   * Get a BYMONTH element.
   *
   * @param \Drupal\date_recur\DateRecurRuleInterface|null $rule
   *   Optional rule for which default value is derived.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldMonth(?DateRecurRuleInterface $rule): array {
    $parts = $rule ? $rule->getParts() : [];
    $monthOptions = DateHelper::monthNames(TRUE);
    $monthDefault = isset($parts['BYMONTH']) ? explode(',', $parts['BYMONTH']) : [];
    return [
      '#type' => 'checkboxes',
      '#title' => $this->t('Months'),
      '#title_display' => 'invisible',
      '#options' => $monthOptions,
      '#default_value' => $monthDefault,
    ];
  }

  /**
   * Get a BYDAY element.
   *
   * @param \Drupal\date_recur\DateRecurRuleInterface|null $rule
   *   Optional rule for which default value is derived.
   * @param string $weekDayLabels
   *   Specify length of weekday labels. Possible values:
   *     - 'full': Full weekday label.
   *     - 'abbreviated': Three character weekday label.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldByDay(?DateRecurRuleInterface $rule, string $weekDayLabels = 'full'): array {
    assert($this->configFactory instanceof ConfigFactoryInterface);
    $parts = $rule ? $rule->getParts() : [];

    $weekdaysKeys = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $weekdayLabels =
      ($weekDayLabels === 'full' ? DateHelper::weekDays(TRUE) :
      ($weekDayLabels === 'abbreviated' ? DateHelper::weekDaysAbbr(TRUE) : []));
    $weekdays = array_combine($weekdaysKeys, $weekdayLabels);

    // Weekday int. 0-6 (Sun-Sat).
    $firstDayInt = $this->configFactory->get('system.date')
      ->get('first_day');

    // Rebuild weekday options where system first day is first option in list.
    $weekdayOptions = array_merge(
      array_slice($weekdays, $firstDayInt),
      // Re-attach weekdays sliced up to the first day.
      array_slice($weekdays, 0, $firstDayInt)
    );

    $weekDayDefault = isset($parts['BYDAY']) ? explode(',', $parts['BYDAY']) : [];
    return [
      '#type' => 'checkboxes',
      '#title_display' => 'invisible',
      '#title' => $this->t('Weekdays'),
      '#options' => $weekdayOptions,
      '#default_value' => $weekDayDefault,
    ];
  }

  /**
   * Get a select element for toggling between common modes.
   *
   * Modes roughly equate to frequencies.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item
   *   A date recur field item.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldMode(DateRecurItem $item): array {
    $modes = $this->getModes();
    return [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => $modes,
      '#default_value' => $this->getMode($item),
      '#access' => count($modes) > 0,
    ];
  }

  /**
   * Get an radios element for toggling between common end modes.
   *
   * @return array
   *   A render array.
   */
  protected function getFieldEndsMode(): array {
    return [
      '#type' => 'radios',
      '#title' => $this->t('Ends'),
      '#options' => [
        DateRecurModularWidgetOptions::ENDS_MODE_INFINITE => $this->t('Never'),
        DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES => $this->t('After number of occurrences'),
        DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE => $this->t('On date'),
      ],
    ];
  }

  /**
   * Builds a #states array for an element dependant on mode selected.
   *
   * @param array $element
   *   The element currently built.
   * @param array $modes
   *   An array of modes which allow an element to be visible.
   *
   * @return array
   *   An array suitable for assignment to a form API '#states' key.
   */
  protected function getVisibilityStates(array $element, array $modes): array {
    $modeFieldName = $this->getName($element, ['mode']);

    $conditions = [];
    foreach ($modes as $mode) {
      if (count($conditions) > 0) {
        $conditions[] = 'or';
      }
      $conditions[] = [':input[name="' . $modeFieldName . '"]' => ['value' => $mode]];
    }

    return ['visible' => $conditions];
  }

}
