<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DateRecurTimeZone constraint.
 */
class DateRecurTimeZoneConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($constraint instanceof DateRecurTimeZoneConstraint);
    $timeZones = \DateTimeZone::listIdentifiers();
    if (is_string($value) && !in_array($value, $timeZones)) {
      $this->context->addViolation($constraint->invalidTimeZone, ['%value' => $value]);
    }
  }

}
