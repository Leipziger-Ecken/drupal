<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\Component\Assertion\Inspector;
use Drupal\date_recur\Rl\RlHelper;

/**
 * Helper for recurring rules.
 *
 * Provides a helper for getting occurrences from a RRULE. The class can be
 * iterated apon, producing occurrence objects beginning at the first
 * occurrence.
 *
 * This helper is a proxy to the default helper. It should be used if there is
 * no preference of implementation.
 */
final class DateRecurHelper implements DateRecurHelperInterface {

  /**
   * The date recur helper.
   *
   * @var \Drupal\date_recur\DateRecurHelperInterface
   */
  protected $dateRecurHelper;

  /**
   * DateRecurHelper constructor.
   *
   * @param \Drupal\date_recur\DateRecurHelperInterface $dateRecurHelper
   *   The date recur helper.
   */
  public function __construct(DateRecurHelperInterface $dateRecurHelper) {
    $this->dateRecurHelper = $dateRecurHelper;
  }

  /**
   * Create a instance of helper using system default.
   *
   * @param string $string
   *   The repeat rule.
   * @param \DateTimeInterface $dtStart
   *   The initial occurrence start date.
   * @param \DateTimeInterface|null $dtStartEnd
   *   The initial occurrence end date, or NULL to use start date.
   *
   * @return static
   *   A rule helper.
   *
   * @throws \Exception
   *   Throws various exceptions if string is invalid.
   */
  public static function create(string $string, \DateTimeInterface $dtStart, \DateTimeInterface $dtStartEnd = NULL) {
    // @todo: get the helper preference from Drupal module config.
    /** @var \Drupal\date_recur\DateRecurHelperInterface $dateRecurHelper */
    $dateRecurHelper = RlHelper::createInstance($string, $dtStart, $dtStartEnd);
    return new static($dateRecurHelper);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(string $string, \DateTimeInterface $dtStart, ?\DateTimeInterface $dtStartEnd = NULL): DateRecurHelperInterface {
    throw new \LogicException('Create instance must not be called on this helper.');
  }

  /**
   * {@inheritdoc}
   */
  public function getRules(): array {
    $rules = $this->dateRecurHelper->getRules();
    assert(is_array($rules));
    assert(Inspector::assertAllObjects($rules, DateRecurRuleInterface::class));
    return $rules;
  }

  /**
   * {@inheritdoc}
   */
  public function isInfinite(): bool {
    return $this->dateRecurHelper->isInfinite();
  }

  /**
   * {@inheritdoc}
   */
  public function generateOccurrences(?\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL): \Generator {
    return $this->dateRecurHelper->generateOccurrences($rangeStart, $rangeEnd);
  }

  /**
   * {@inheritdoc}
   */
  public function getOccurrences(\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL, ?int $limit = NULL): array {
    return $this->dateRecurHelper->getOccurrences($rangeStart, $rangeEnd, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getExcluded(): array {
    $exDates = $this->dateRecurHelper->getExcluded();
    assert(Inspector::assertAllObjects($exDates, \DateTimeInterface::class));
    return $exDates;
  }

  /**
   * {@inheritdoc}
   */
  public function current(): DateRange {
    return $this->dateRecurHelper->current();
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    $this->dateRecurHelper->next();
  }

  /**
   * {@inheritdoc}
   */
  public function key(): ?int {
    return $this->dateRecurHelper->key();
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return $this->dateRecurHelper->valid();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->dateRecurHelper->rewind();
  }

}
