<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates RRULE strings.
 *
 * @Constraint(
 *   id = "DateRecurRrule",
 *   label = @Translation("Validates RRULEs", context = "Validation"),
 * )
 */
class DateRecurRruleConstraint extends Constraint {

  /**
   * Violation message for an invalid RRULE.
   *
   * @var string
   */
  public $invalidRrule = 'Invalid RRULE.';

}
