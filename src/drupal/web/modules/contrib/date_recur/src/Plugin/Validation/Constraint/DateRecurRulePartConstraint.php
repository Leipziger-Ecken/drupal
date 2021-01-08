<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Restricts parts in RRULE to a pre-defined subset.
 *
 * @Constraint(
 *   id = "DateRecurRuleParts",
 *   label = @Translation("Frequency and part restriction", context = "Validation"),
 * )
 */
class DateRecurRulePartConstraint extends Constraint {

  /**
   * Violation message when a part is not permitted.
   *
   * @var string
   */
  public $disallowedPart = '%part is not a permitted part.';

  /**
   * Violation message when a frequency is not permitted.
   *
   * @var string
   */
  public $disallowedFrequency = '%frequency is not a permitted frequency.';

  /**
   * Violation message when a part is incompatible with a frequency.
   *
   * @var string
   */
  public $incompatiblePart = '%part is incompatible with %frequency.';

}
