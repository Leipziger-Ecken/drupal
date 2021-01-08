<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

/**
 * Defines an interface for a single rule.
 *
 * Normalizes rule class implementations.
 */
interface DateRecurRuleInterface {

  /**
   * Get the frequency for the rule.
   *
   * @return string
   *   The frequency for the rule.
   */
  public function getFrequency(): string;

  /**
   * Get the RULE parts.
   *
   * For example, "FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;" will return:
   *
   * @code
   *
   * [
   *   'BYDAY' => 'MO,TU,WE,TH,FR',
   *   'DTSTART' => \DateTime(...),
   *   'FREQ' => 'WEEKLY',
   * ]
   *
   * @endcode
   *
   * @return array
   *   The parts of the RRULE.
   */
  public function getParts(): array;

}
