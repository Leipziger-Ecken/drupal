<?php

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\DateRange;
use Drupal\Tests\UnitTestCase;

/**
 * Tests date range class.
 *
 * @coversDefaultClass \Drupal\date_recur\DateRange
 * @group date_recur
 */
class DateRecurDateRangeUnitTest extends UnitTestCase {

  /**
   * Test arguments required.
   */
  public function testRequiredConstructorArguments() {
    $this->setExpectedException(\ArgumentCountError::class);
    $this->createDateRange();
  }

  /**
   * Tests start and end getters.
   *
   * @covers ::getStart
   * @covers ::getEnd
   */
  public function testGetters() {
    $start = new \DateTime('yesterday');
    $end = new \DateTime('tomorrow');
    $dateRange = $this->createDateRange($start, $end);

    $this->assertEquals($start, $dateRange->getStart());
    $this->assertEquals($end, $dateRange->getEnd());
    $this->assertNotEquals($dateRange->getStart(), $dateRange->getEnd());
  }

  /**
   * Tests references to dates set on date range are lost.
   *
   * @covers ::setStart
   * @covers ::setEnd
   */
  public function testConstructorReferencesLost() {
    $startOriginal = new \DateTime('Monday 12:00:00');
    $endOriginal = new \DateTime('Monday 12:00:00');
    $start = clone $startOriginal;
    $end = clone $endOriginal;

    $dateRange = $this->createDateRange($start, $end);
    // Modify the passed dates.
    $start->modify('+1 year');
    $end->modify('+1 year');

    // Dates should be the same as passed.
    $this->assertEquals($startOriginal, $dateRange->getStart());
    $this->assertEquals($endOriginal, $dateRange->getEnd());
  }

  /**
   * Tests references on start getter is lost.
   *
   * @covers ::getStart
   */
  public function testGetStartImmutable() {
    $original = new \DateTime('Monday 12:00:00');
    $dateRange = $this->createDateRange(clone $original, new \DateTime('Monday 12:00:00'));

    $gotten = $dateRange->getStart();
    $gotten->modify('+1 year');
    $gotten->setTimezone(new \DateTimeZone('Asia/Singapore'));

    $this->assertEquals($original, $dateRange->getStart());
  }

  /**
   * Tests references on end getter is lost.
   *
   * @covers ::getEnd
   */
  public function testGetEndImmutable() {
    $original = new \DateTime('Monday 12:00:00');
    $dateRange = $this->createDateRange(new \DateTime('Monday 12:00:00'), clone $original);

    $gotten = $dateRange->getEnd();
    $gotten->modify('+1 year');
    $gotten->setTimezone(new \DateTimeZone('Asia/Singapore'));

    $this->assertEquals($original, $dateRange->getEnd());
  }

  /**
   * Tests end occur on or after start.
   *
   * @covers ::validateDates
   */
  public function testEndAfterStartValidation() {
    // Same time.
    $start = new \DateTime('Monday 12:00:00');
    $end = new \DateTime('Monday 12:00:00');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    // End after start.
    $start = new \DateTime('Monday 12:00:00');
    $end = new \DateTime('Monday 12:00:01');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    $start = new \DateTime('Monday 12:00:01');
    $end = new \DateTime('Monday 12:00:00');

    $this->setExpectedException(\InvalidArgumentException::class, 'End date must not occur before start date.');
    $this->createDateRange($start, $end);
  }

  /**
   * Tests exception raised if timezones are not the same.
   *
   * The exception exists to catch any potential logic mishaps.
   *
   * @covers ::validateDates
   */
  public function testTimezoneValidation() {
    $start = new \DateTime('Monday 12:00:00', new \DateTimeZone('Australia/Melbourne'));
    $end = new \DateTime('Monday 12:00:00', new \DateTimeZone('Australia/Sydney'));
    $this->setExpectedException(\InvalidArgumentException::class, 'Provided dates must be the same timezone.');
    $this->createDateRange($start, $end);
  }

  /**
   * Create a new range.
   *
   * Do not type-hint the args.
   *
   * @param mixed $start
   *   The start date.
   * @param mixed $end
   *   The end date.
   *
   * @return \Drupal\date_recur\DateRange
   *   New range object.
   */
  protected function createDateRange($start, $end) {
    return new DateRange($start, $end);
  }

}
