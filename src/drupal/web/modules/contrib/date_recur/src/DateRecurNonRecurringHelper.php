<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

/**
 * Dummy helper for handling non-recurring values.
 */
class DateRecurNonRecurringHelper implements DateRecurHelperInterface {

  /**
   * The occurrences.
   *
   * @var \Drupal\date_recur\DateRange[]
   */
  protected $occurrences = [];

  /**
   * Constructor for DateRecurNonRecurringHelper.
   *
   * @param \DateTimeInterface $dtStart
   *   The initial occurrence start date.
   * @param \DateTimeInterface|null $dtStartEnd
   *   The initial occurrence end date, or NULL to use start date.
   */
  public function __construct(\DateTimeInterface $dtStart, \DateTimeInterface $dtStartEnd = NULL) {
    $dtStartEnd = $dtStartEnd ?? clone $dtStart;
    $this->occurrences = [new DateRange($dtStart, $dtStartEnd)];
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(string $string, \DateTimeInterface $dtStart, ?\DateTimeInterface $dtStartEnd = NULL): DateRecurHelperInterface {
    return new static($dtStart, $dtStartEnd);
  }

  /**
   * {@inheritdoc}
   */
  public function getRules(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInfinite(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function generateOccurrences(?\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL): \Generator {
    foreach ($this->occurrences as $occurrence) {
      $occurrenceStart = $occurrence->getStart();
      $occurrenceEnd = $occurrence->getEnd();

      if ($rangeStart) {
        if ($occurrenceStart < $rangeStart && $occurrenceEnd < $rangeStart) {
          continue;
        }
      }

      if ($rangeEnd) {
        if ($occurrenceStart > $rangeEnd && $occurrenceEnd > $rangeEnd) {
          break;
        }
      }

      yield $occurrence;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOccurrences(\DateTimeInterface $rangeStart = NULL, ?\DateTimeInterface $rangeEnd = NULL, ?int $limit = NULL): array {
    if (isset($limit) && (!is_int($limit) || $limit < 0)) {
      // Limit must be a number and more than one.
      throw new \InvalidArgumentException('Invalid count limit.');
    }

    // There can either by zero or one occurrence generated for non-recurring
    // generator.
    if (isset($limit) && $limit === 0) {
      return [];
    }

    return iterator_to_array($this->generateOccurrences($rangeStart, $rangeEnd));
  }

  /**
   * {@inheritdoc}
   */
  public function getExcluded(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function current(): DateRange {
    return current($this->occurrences);
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    next($this->occurrences);
  }

  /**
   * {@inheritdoc}
   */
  public function key(): ?int {
    $key = key($this->occurrences);
    assert(is_int($key));
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    reset($this->occurrences);
  }

}
