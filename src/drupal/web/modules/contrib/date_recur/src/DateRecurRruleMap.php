<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Rrule maps.
 */
final class DateRecurRruleMap {

  /**
   * Frequencies.
   *
   * In no particular order.
   */
  public const FREQUENCIES = [
    'SECONDLY',
    'MINUTELY',
    'HOURLY',
    'DAILY',
    'WEEKLY',
    'MONTHLY',
    'YEARLY',
  ];

  /**
   * Parts.
   *
   * In no particular order.
   */
  public const PARTS = [
    'DTSTART',
    'UNTIL',
    'COUNT',
    'INTERVAL',
    'BYSECOND',
    'BYMINUTE',
    'BYHOUR',
    'BYDAY',
    'BYMONTHDAY',
    'BYYEARDAY',
    'BYWEEKNO',
    'BYMONTH',
    'BYSETPOS',
    'WKST',
  ];

  /**
   * Incompatible parts.
   *
   * Specifies parts which are incompatible with frequencies.
   *
   * @see https://tools.ietf.org/html/rfc5545#page-44
   */
  public const INCOMPATIBLE_PARTS = [
    'SECONDLY' => ['BYWEEKNO'],
    'MINUTELY' => ['BYWEEKNO'],
    'HOURLY' => ['BYWEEKNO'],
    'DAILY' => ['BYWEEKNO', 'BYYEARDAY'],
    'WEEKLY' => ['BYWEEKNO', 'BYYEARDAY', 'BYMONTHDAY'],
    'MONTHLY' => ['BYWEEKNO', 'BYYEARDAY'],
    'YEARLY' => [],
  ];

  /**
   * Labels for parts.
   *
   * @return array
   *   Labels for parts keyed by part.
   */
  public static function partLabels(): array {
    return [
      'DTSTART' => new TranslatableMarkup('Start date'),
      'UNTIL' => new TranslatableMarkup('Until'),
      'COUNT' => new TranslatableMarkup('Count'),
      'INTERVAL' => new TranslatableMarkup('Interval'),
      'BYSECOND' => new TranslatableMarkup('By-second'),
      'BYMINUTE' => new TranslatableMarkup('By-minute'),
      'BYHOUR' => new TranslatableMarkup('By-hour'),
      'BYDAY' => new TranslatableMarkup('By-day'),
      'BYMONTHDAY' => new TranslatableMarkup('By-day-of-month'),
      'BYYEARDAY' => new TranslatableMarkup('By-day-of-year'),
      'BYWEEKNO' => new TranslatableMarkup('By-week-number'),
      'BYMONTH' => new TranslatableMarkup('By-month'),
      'BYSETPOS' => new TranslatableMarkup('By-set-position'),
      'WKST' => new TranslatableMarkup('Week start'),
    ];
  }

  /**
   * Labels for frequencies.
   *
   * @return array
   *   Labels for frequencies keyed by frequency.
   */
  public static function frequencyLabels(): array {
    return [
      'SECONDLY' => new TranslatableMarkup('Secondly'),
      'MINUTELY' => new TranslatableMarkup('Minutely'),
      'HOURLY' => new TranslatableMarkup('Hourly'),
      'DAILY' => new TranslatableMarkup('Daily'),
      'WEEKLY' => new TranslatableMarkup('Weekly'),
      'MONTHLY' => new TranslatableMarkup('Monthly'),
      'YEARLY' => new TranslatableMarkup('Yearly'),
    ];
  }

  /**
   * Descriptions for parts.
   *
   * @return array
   *   Descriptions for parts keyed by part.
   */
  public static function partDescriptions(): array {
    return [
      'DTSTART' => new TranslatableMarkup('The starting date.'),
      'UNTIL' => new TranslatableMarkup('Specify a date occurrences cannot be generated after.'),
      'COUNT' => new TranslatableMarkup('Specify number of occurrences.'),
      'INTERVAL' => new TranslatableMarkup('Specify at an interval where the repeating rule repeats.'),
      'BYSECOND' => new TranslatableMarkup('Specify the second(s) where a repeating rule repeats.'),
      'BYMINUTE' => new TranslatableMarkup('Specify the minute(s) where a repeating rule repeats.'),
      'BYHOUR' => new TranslatableMarkup('Specify the hour(s) where a repeating rule repeats.'),
      'BYDAY' => new TranslatableMarkup('Specify the weekday(s) where a repeating rule repeats.'),
      'BYMONTHDAY' => new TranslatableMarkup('Specify the day number(s) in a month where a repeating rule repeats.'),
      'BYYEARDAY' => new TranslatableMarkup('Specify the day number(s) in a year where a repeating rule repeats.'),
      'BYWEEKNO' => new TranslatableMarkup('Specify the week number(s) in a year where a repeating rule repeats.'),
      'BYMONTH' => new TranslatableMarkup('Specify the month(s) where a repeating rule repeats.'),
      'BYSETPOS' => new TranslatableMarkup('Specify the the nth occurrence(s) in combination with other BY rules to limit occurrences.'),
      'WKST' => new TranslatableMarkup('Specify the first day of the week.'),
    ];
  }

}
