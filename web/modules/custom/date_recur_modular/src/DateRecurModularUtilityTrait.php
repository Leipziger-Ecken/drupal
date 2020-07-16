<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\DateRecurRuleInterface;
use Drupal\date_recur\Exception\DateRecurHelperArgumentException;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Trait containing convenience methods for dealing with date recur widgets.
 *
 * @property \Drupal\Core\Field\FieldDefinitionInterface fieldDefinition
 */
trait DateRecurModularUtilityTrait {

  /**
   * Build a datetime object by getting the date and time from two fields.
   *
   * @param array $dayField
   *   Form path to the day field.
   * @param array $timeField
   *   Form path to the time field.
   * @param string $timeZone
   *   Time zone for the day.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state object.
   *
   * @return \DateTime
   *   A date object.
   *
   * @throws \Exception
   *   Exceptions thrown if input is invalid.
   */
  public static function buildDatesFromFields(array $dayField, array $timeField, string $timeZone, FormStateInterface $formState): \DateTime {
    $tz = new \DateTimeZone($timeZone);
    $completeForm = $formState->getCompleteForm();
    $fieldA = NestedArray::getValue($completeForm, $dayField);
    $fieldB = NestedArray::getValue($completeForm, $timeField);

    assert($fieldA['#type'] === 'date');
    assert($fieldB['#type'] === 'date' && $fieldB['#attributes']['type'] === 'time');

    $valueA = $formState->getValue($fieldA['#parents']);
    $valueB = $formState->getValue($fieldB['#parents']);

    // Create base day object.
    $baseDay = \DateTime::createFromFormat('Y-m-d', $valueA, $tz);
    if (!$baseDay) {
      throw new \Exception('Input for date is invalid.');
    }

    $baseDay->setTime(0, 0, 0);
    $baseDayParts = explode('-', $baseDay->format('Y-n-j'));
    $baseDayParts = array_map('intval', $baseDayParts);

    // Fix the time. HTML element allows omitting seconds.
    if (!empty($valueB) && strlen($valueB) == 5) {
      $valueB .= ':00';
    }

    $time = \DateTime::createFromFormat('H:i:s', $valueB, $tz);
    if (!$time) {
      throw new \Exception('Input for time is invalid.');
    }
    $time->setDate(...$baseDayParts);
    return $time;
  }

  /**
   * Build the name for a sub element.
   *
   * @param array $element
   *   The element render array.
   * @param array $subNames
   *   The full render array path to sub element.
   *
   * @return string
   *   The sub element name
   */
  protected function getName(array $element, array $subNames): string {
    assert($this->fieldDefinition instanceof FieldDefinitionInterface);
    $parents = $element['#field_parents'];
    $parents[] = $this->fieldDefinition->getName();
    $selector = $root = array_shift($parents);
    if ($parents) {
      $selector = $root . '[' . implode('][', $parents) . ']';
    }
    return sprintf('%s[%d][%s]', $selector, $element['#delta'], implode('][', $subNames));
  }

  /**
   * Determines a default time zone for a field item.
   *
   * If the provided field item does not have time zone data, then a sensible
   * time zone will be determined based on the current user and site
   * configuration.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item
   *   A date recur field item.
   *
   * @return string
   *   A time zone.
   */
  protected function getDefaultTimeZone(DateRecurItem $item): string {
    assert($this->fieldDefinition instanceof FieldDefinitionInterface);
    $defaultTimeZone = $item->timezone ?? NULL;
    if (!$defaultTimeZone && empty($item->getValue())) {
      // Handily set default value to the field settings default time zone if
      // value is empty. This typically happens if entity is new or this is a
      // blank extra field value.
      $defaultTimeZone = $this->fieldDefinition->getDefaultValueLiteral()[0]['default_time_zone'] ?? NULL;
    }
    // If still blank then use current user time zone.
    if (empty($defaultTimeZone)) {
      $defaultTimeZone = $this->getCurrentUserTimeZone();
    }
    return $defaultTimeZone;
  }

  /**
   * Attempts to get the first valid rule from a date recur field item.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item
   *   A date recur field item.
   *
   * @return \Drupal\date_recur\DateRecurRuleInterface|null
   *   A rule.
   */
  protected function getRule(DateRecurItem $item): ?DateRecurRuleInterface {
    try {
      $helper = $item->getHelper();
    }
    catch (DateRecurHelperArgumentException $e) {
      return NULL;
    }

    $rules = $helper->getRules();
    $rule = reset($rules);
    return FALSE !== $rule ? $rule : NULL;
  }

  /**
   * Builds RRULE string from an array of parts, stripping disallowed parts.
   *
   * @param array $parts
   *   An array of parts, including 'FREQ'.
   * @param \Drupal\date_recur\DateRecurPartGrid $grid
   *   A part grid. Any disallowed frequencies or parts are stripped silently.
   *
   * @return string
   *   An RRULE string ready for storage.
   */
  protected function buildRruleString(array $parts, DateRecurPartGrid $grid): string {
    $frequency = $parts['FREQ'];
    foreach (array_keys($parts) as $part) {
      if ($part === 'FREQ') {
        continue;
      }
      if (!$grid->isPartAllowed($frequency, $part)) {
        unset($parts[$part]);
      }
    }

    $ruleKv = [];
    foreach ($parts as $k => $v) {
      $ruleKv[] = "$k=$v";
    }
    return implode(';', $ruleKv);
  }

  /**
   * Get a list of time zones suitable for a select field.
   *
   * @return array
   *   A list of time zones where keys are PHP time zone codes, and values are
   *   human readable and translatable labels.
   */
  protected function getTimeZoneOptions(): array {
    return \system_time_zones(TRUE, TRUE);
  }

  /**
   * Get the time zone associated with the current user.
   *
   * @return string
   *   A time zone.
   */
  protected function getCurrentUserTimeZone(): string {
    return \date_default_timezone_get();
  }

  /**
   * Determine whether a field item represents a full day.
   *
   * Perspective of full day is determined by the current user [timezone].
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item
   *   Date recur field item.
   * @param bool $sameDay
   *   Whether dates must be the same calendar day.
   *
   * @return bool
   *   Whether a field item represents a full day.
   *
   * @throws \Exception
   *   If account has an invalid time zone.
   */
  protected function isAllDay(DateRecurItem $item, bool $sameDay = FALSE): bool {
    $startDate = $item->start_date ? clone $item->start_date : NULL;
    $endDate = $item->end_date ? clone $item->end_date : NULL;
    if ($startDate && $endDate) {
      $timeZoneRaw = $this->getCurrentUserTimeZone();
      $accountTimeZone = new \DateTimeZone($timeZoneRaw);
      $startDate->setTimezone($accountTimeZone);
      $endDate->setTimezone($accountTimeZone);

      // Calendar day is same.
      if ($sameDay && ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d'))) {
        return FALSE;
      }

      // Ignore seconds when checking whether time is all day.
      return (substr($startDate->format('H:i'), 0, 5) === '00:00') &&
        (substr($endDate->format('H:i'), 0, 5) === '23:59');
    }
    return FALSE;
  }

  /**
   * Determine nth weekday into a month for a date.
   *
   * Given the weekday and month for a date, attempt to determine how many
   * weekdays into the month.
   *
   * @param \DateTime $date
   *   A date.
   *
   * @return int
   *   A number between 1 and 5.
   */
  public static function getMonthWeekdayNth(\DateTime $date): int {
    $monthWeekdayNth = 0;
    $weekdayNumber = $date->format('w');
    $interval = new \DateInterval('P1D');
    $iter = clone $date;
    $iter->setDate((int) $iter->format('Y'), (int) $iter->format('m'), 1);
    while ($iter <= $date) {
      if ($iter->format('w') === $weekdayNumber) {
        $monthWeekdayNth++;
      }
      $iter->add($interval);
    }
    return $monthWeekdayNth;
  }

}
