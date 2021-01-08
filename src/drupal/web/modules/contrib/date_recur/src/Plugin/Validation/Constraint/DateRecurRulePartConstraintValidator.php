<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Validation\Constraint;

use Drupal\date_recur\DateRecurHelper;
use Drupal\date_recur\DateRecurRruleMap;
use Drupal\date_recur\Exception\DateRecurRulePartIncompatible;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DateRecurRulePartConstraint constraint.
 */
class DateRecurRulePartConstraintValidator extends ConstraintValidator {

  /**
   * Labels for frequencies.
   *
   * @var array|null
   */
  protected $frequencyLabels = NULL;

  /**
   * Labels for parts.
   *
   * @var array|null
   */
  protected $partLabels = NULL;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($value instanceof DateRecurItem);
    assert($constraint instanceof DateRecurRulePartConstraint);
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $fieldList */
    $fieldList = $value->getParent();
    $grid = $fieldList->getPartGrid();

    // Validator do not apply to field values without RRULE.
    if (empty($value->rrule)) {
      return;
    }

    // Catch exceptions thrown by invalid rules.
    try {
      // Use a fake start time as there may be an empty or invalid start date.
      $helper = DateRecurHelper::create($value->rrule, new \DateTime());
    }
    catch (\Exception $e) {
      // Invalid RRULE's are handled by DateRecurRruleConstraint.
      return;
    }

    foreach ($helper->getRules() as $rule) {
      /** @var \Drupal\date_recur\DateRecurRuleInterface $rule */
      $frequency = $rule->getFrequency();
      // Check if a frequency is supported.
      if (!$grid->isFrequencyAllowed($frequency)) {
        $frequencyLabels = $this->getFrequencyLabels();
        $frequencyLabel = $frequencyLabels[$frequency] ?? $frequency;
        $this->context->addViolation($constraint->disallowedFrequency, ['%frequency' => $frequencyLabel]);

        // If the frequency isn't supported then dont continue validating its
        // parts as it creates redundant violations.
        continue;
      }

      $parts = $rule->getParts();
      unset($parts['FREQ']);
      foreach (array_keys($parts) as $part) {
        try {
          // Check if a part is supported.
          if (!$grid->isPartAllowed($frequency, $part)) {
            $partLabels = $this->getPartLabels();
            $partLabel = $partLabels[$part] ?? $part;
            $this->context->addViolation($constraint->disallowedPart, ['%part' => $partLabel]);
          }
        }
        catch (DateRecurRulePartIncompatible $e) {
          // If a part is incompatible, add a violation.
          $frequencyLabels = $this->getFrequencyLabels();
          $frequencyLabel = $frequencyLabels[$frequency] ?? $frequency;
          $partLabels = $this->getPartLabels();
          $partLabel = $partLabels[$part] ?? $part;
          $this->context->addViolation($constraint->incompatiblePart, [
            '%frequency' => $frequencyLabel,
            '%part' => $partLabel,
          ]);
        }
      }
    }
  }

  /**
   * Labels for frequencies.
   *
   * @return array
   *   Labels for frequencies keyed by part.
   */
  protected function getFrequencyLabels(): array {
    if (!isset($this->frequencyLabels)) {
      $this->frequencyLabels = DateRecurRruleMap::frequencyLabels();
    }
    return $this->frequencyLabels;
  }

  /**
   * Labels for parts.
   *
   * @return array
   *   Labels for parts keyed by part.
   */
  protected function getPartLabels(): array {
    if (!isset($this->partLabels)) {
      $this->partLabels = DateRecurRruleMap::partLabels();
    }
    return $this->partLabels;
  }

}
