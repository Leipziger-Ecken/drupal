<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\date_recur\Exception\DateRecurRulePartIncompatible;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Frequency/part support grid.
 */
class DateRecurPartGrid {

  /**
   * Supported parts for this part grid.
   *
   * @var array
   *   Parts keyed by frequency.
   */
  protected $allowedParts = [];

  /**
   * Adds parts for a frequency to the allow list.
   *
   * @param string $frequency
   *   A frequency.
   * @param string[] $parts
   *   An array of parts.
   */
  public function allowParts(string $frequency, array $parts): void {
    $existingFrequencyParts = $this->allowedParts[$frequency] ?? [];
    $this->allowedParts[$frequency] = array_merge($parts, $existingFrequencyParts);
  }

  /**
   * Determines whether all parts and frequencies are supported.
   */
  public function isAllowEverything(): bool {
    return count($this->allowedParts) === 0;
  }

  /**
   * Determines whether a frequency and at least one part is supported.
   *
   * @param string $frequency
   *   A frequency.
   *
   * @return bool
   *   Whether a frequency is supported.
   */
  public function isFrequencyAllowed(string $frequency): bool {
    assert(in_array($frequency, DateRecurRruleMap::FREQUENCIES, TRUE));
    if ($this->isAllowEverything()) {
      return TRUE;
    }

    return isset($this->allowedParts[$frequency]) && count($this->allowedParts[$frequency]) > 0;
  }

  /**
   * Determines whether a part is allowed.
   *
   * @param string $frequency
   *   A frequency.
   * @param string $part
   *   A part.
   *
   * @return bool
   *   Whether a part is supported.
   *
   * @throws \Drupal\date_recur\Exception\DateRecurRulePartIncompatible
   *   Part is incompatible with frequency.
   */
  public function isPartAllowed(string $frequency, string $part): bool {
    assert(in_array($frequency, DateRecurRruleMap::FREQUENCIES, TRUE) && in_array($part, DateRecurRruleMap::PARTS, TRUE));
    if (in_array($part, DateRecurRruleMap::INCOMPATIBLE_PARTS[$frequency], TRUE)) {
      throw new DateRecurRulePartIncompatible();
    }

    if ($this->isAllowEverything()) {
      return TRUE;
    }

    $partsInFrequency = $this->allowedParts[$frequency] ?? [];
    // Supports the part, or everything in this frequency.
    return in_array($part, $partsInFrequency, TRUE) || in_array(DateRecurItem::PART_SUPPORTS_ALL, $partsInFrequency, TRUE);
  }

  /**
   * Converts settings from date recur field configuration to a part grid.
   *
   * @param array $parts
   *   Part configuration.
   *
   * @return \Drupal\date_recur\DateRecurPartGrid
   *   A new parts grid.
   */
  public static function configSettingsToGrid(array $parts) {
    $grid = new static();

    if (!empty($parts['all'])) {
      return $grid;
    }

    $frequencies = $parts['frequencies'] ?? [];
    foreach ($frequencies as $frequency => $frequencyParts) {
      $grid->allowParts($frequency, $frequencyParts);
    }

    return $grid;
  }

}
