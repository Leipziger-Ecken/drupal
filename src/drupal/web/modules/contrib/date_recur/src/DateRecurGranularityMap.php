<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Granularity maps.
 */
final class DateRecurGranularityMap {

  /**
   * Granularities and their associated weight.
   *
   * Larger weights correspond to smaller time units.
   */
  public const GRANULARITY = [
    'year' => 1,
    'month' => 2,
    'day' => 3,
    'second' => 6,
  ];

  /**
   * Granularities and their associated date() format.
   */
  public const GRANULARITY_DATE_FORMATS = [
    'year' => 'Y',
    'month' => 'Y-m',
    'day' => 'Y-m-d',
    'second' => 'Y-m-d\TH:i:s',
  ];

  /**
   * Granularities and their associated regex for validation.
   */
  public const GRANULARITY_EXPRESSIONS = [
    'year' => '/^\d{4}$/',
    'month' => '/^\d{4}\-\d{2}$/',
    'day' => '/^\d{4}\-\d{2}-\d{2}$/',
    'second' => '/^\d{4}\-\d{2}\-\d{2}\T\d{2}:\d{2}:\d{2}$/',
  ];

  /**
   * Granularities and their associated labels.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Labels for granularities keyed by granularity.
   */
  public static function granularityLabels(): array {
    return [
      'year' => new TranslatableMarkup('Absolute year'),
      'month' => new TranslatableMarkup('Absolute month'),
      'day' => new TranslatableMarkup('Absolute day'),
      'second' => new TranslatableMarkup('Datetime'),
    ];
  }

  /**
   * Granularities and their associated failed validation message labels.
   *
   * @param string $sample
   *   A sample string.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Failed validation messages for granularities keyed by granularity.
   */
  public static function granularityExpectedFormatMessages(string $sample): array {
    return [
      'year' => \t('YYYY (Year, for example: @sample)', ['@sample' => $sample]),
      'month' => \t('YYYY-MM (Year-month, for example: @sample)', ['@sample' => $sample]),
      'day' => \t('YYYY-MM-DD (Year-month-day, for example: @sample)', ['@sample' => $sample]),
      'second' => \t('YYYY-MM-DDTHH:MM:SS (for example: @sample)', ['@sample' => $sample]),
    ];
  }

}
