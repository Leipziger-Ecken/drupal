<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

/**
 * Defines a date range.
 */
class DateRange {

  /**
   * The start date.
   *
   * @var \DateTimeInterface
   */
  protected $start;

  /**
   * The end date.
   *
   * @var \DateTimeInterface
   */
  protected $end;

  /**
   * Creates a new DateRange.
   *
   * @param \DateTimeInterface $start
   *   The start date.
   * @param \DateTimeInterface $end
   *   The end date.
   */
  public function __construct(\DateTimeInterface $start, \DateTimeInterface $end) {
    $this->start = clone $start;
    $this->end = clone $end;
    $this->validateDates();
  }

  /**
   * Get the start date.
   *
   * @return \DateTimeInterface
   *   The start date.
   */
  public function getStart(): \DateTimeInterface {
    return clone $this->start;
  }

  /**
   * Set the start date.
   *
   * @param \DateTimeInterface $start
   *   The start date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setStart(\DateTimeInterface $start) {
    // Clone to ensure references are lost.
    $this->start = clone $start;
    $this->validateDates();
    return $this;
  }

  /**
   * Get the end date.
   *
   * @return \DateTimeInterface
   *   The end date.
   */
  public function getEnd(): \DateTimeInterface {
    return clone $this->end;
  }

  /**
   * Set the end date.
   *
   * @param \DateTimeInterface $end
   *   The end date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setEnd(\DateTimeInterface $end) {
    // Clone to ensure references are lost.
    $this->end = clone $end;
    $this->validateDates();
    return $this;
  }

  /**
   * Validates the start and end dates.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  protected function validateDates(): void {
    // Normalize end date timezone.
    if ($this->start->getTimezone()->getName() !== $this->end->getTimezone()->getName()) {
      throw new \InvalidArgumentException('Provided dates must be the same timezone.');
    }

    if ($this->end < $this->start) {
      throw new \InvalidArgumentException('End date must not occur before start date.');
    }
  }

}
