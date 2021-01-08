<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\DateRecurUtility;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests utility class.
 *
 * @group date_recur
 * @coversDefaultClass \Drupal\date_recur\DateRecurUtility
 */
class DateRecurUtilityTest extends KernelTestBase {

  /**
   * Tests smallest date utility.
   *
   * @param string $granularity
   *   A granularity.
   * @param string $value
   *   An input value, assuming Singapore is the timezone.
   * @param string $expected
   *   The expected date, in date()'s 'r' format.
   *
   * @covers ::createSmallestDateFromInput
   * @dataProvider providerSmallestDate
   */
  public function testSmallestDate($granularity, $value, $expected) {
    $timezone = new \DateTimeZone('Asia/Singapore');
    $smallest = DateRecurUtility::createSmallestDateFromInput($granularity, $value, $timezone);
    $this->assertEquals($expected, $smallest->format('r'));
  }

  /**
   * Data provider for testSmallestDate.
   *
   * @return array
   *   Data for testing.
   */
  public function providerSmallestDate() {
    $data = [];

    $data['year'] = [
      'year',
      '2014',
      'Wed, 01 Jan 2014 00:00:00 +0800',
    ];
    $data['month'] = [
      'month',
      '2014-10',
      'Wed, 01 Oct 2014 00:00:00 +0800',
    ];
    $data['day'] = [
      'day',
      '2014-10-02',
      'Thu, 02 Oct 2014 00:00:00 +0800',
    ];
    $data['second'] = [
      'second',
      '2014-10-02T11:30:49',
      'Thu, 02 Oct 2014 11:30:49 +0800',
    ];

    return $data;
  }

  /**
   * Tests largest date utility.
   *
   * @param string $granularity
   *   A granularity.
   * @param string $value
   *   An input value, assuming Singapore is the timezone.
   * @param string $expected
   *   The expected date, in date()'s 'r' format.
   *
   * @covers ::createLargestDateFromInput
   * @dataProvider providerLargestDate
   */
  public function testLargestDate($granularity, $value, $expected) {
    $timezone = new \DateTimeZone('Asia/Singapore');
    $largest = DateRecurUtility::createLargestDateFromInput($granularity, $value, $timezone);
    $this->assertEquals($expected, $largest->format('r'));
  }

  /**
   * Data provider for testLargestDate.
   *
   * @return array
   *   Data for testing.
   */
  public function providerLargestDate() {
    $data = [];

    $data['year'] = [
      'year',
      '2014',
      'Wed, 31 Dec 2014 23:59:59 +0800',
    ];
    $data['month'] = [
      'month',
      '2014-10',
      'Fri, 31 Oct 2014 23:59:59 +0800',
    ];
    $data['day'] = [
      'day',
      '2014-10-02',
      'Thu, 02 Oct 2014 23:59:59 +0800',
    ];
    $data['second'] = [
      'second',
      '2014-10-02T11:30:49',
      'Thu, 02 Oct 2014 11:30:49 +0800',
    ];

    return $data;
  }

}
