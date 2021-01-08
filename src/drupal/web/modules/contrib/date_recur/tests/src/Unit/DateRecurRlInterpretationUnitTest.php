<?php

namespace Drupal\Tests\date_recur\Unit;

use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\date_recur\Plugin\DateRecurInterpreter\RlInterpreter;
use Drupal\date_recur\Rl\RlDateRecurRule;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Rlanvin implementation of interpreter.
 *
 * Interpretations come from the RLanvin library, test the basics here.
 *
 * @coversDefaultClass \Drupal\date_recur\Plugin\DateRecurInterpreter\RlInterpreter
 * @group date_recur
 *
 * @ingroup RLanvinPhpRrule
 */
class DateRecurRlInterpretationUnitTest extends UnitTestCase {

  /**
   * A test container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $testContainer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $dateFormat = $this->createMock(DateFormatInterface::class);
    $dateFormat->expects($this->any())
      ->method('id')
      ->willReturn($this->randomMachineName());

    $dateFormatStorage = $this->createMock(EntityStorageInterface::class);
    $dateFormatStorage->expects($this->any())
      ->method('load')
      ->with($this->anything())
      ->willReturn($dateFormat);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('date_format')
      ->willReturn($dateFormatStorage);

    $dateFormatter = $this->createMock(DateFormatterInterface::class);
    $dateFormatter->expects($this->any())
      ->method('format')
      ->with($this->anything())
      // See \Drupal\Core\Datetime\DateFormatterInterface::format.
      ->willReturnCallback(function ($timestamp, $type = 'medium', $format = '', string $timezone = NULL, $langcode = NULL) {
        $date = new \DateTime('@' . $timestamp);
        if (!$timezone) {
          $timezone = date_default_timezone_get();
        }
        $date->setTimezone(new \DateTimeZone($timezone));
        return $date->format('r');
      });

    $container = new ContainerBuilder();
    $container->set('date.formatter', $dateFormatter);
    $container->set('entity_type.manager', $entityTypeManager);
    $this->testContainer = $container;
  }

  /**
   * Tests secondly interpretation.
   */
  public function testSecondly() {
    $parts = [
      'FREQ' => 'SECONDLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYSECOND' => '59',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('secondly at second 59, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests minutely interpretation.
   */
  public function testMinutely() {
    $parts = [
      'FREQ' => 'MINUTELY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMINUTE' => '44',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('minutely at minute 44, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests hourly interpretation.
   */
  public function testHourly() {
    $parts = [
      'FREQ' => 'HOURLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYHOUR' => '4,7',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('hourly at 4h and 7h, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests daily interpretation.
   */
  public function testDaily() {
    $parts = [
      'FREQ' => 'DAILY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'WE,SU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('daily on Wednesday and Sunday, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests weekly interpretation.
   */
  public function testWeekly() {
    $parts = [
      'FREQ' => 'WEEKLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYDAY' => 'MO,TU',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('weekly on Monday and Tuesday, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests monthly interpretation.
   */
  public function testMonthly() {
    $parts = [
      'FREQ' => 'MONTHLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
      'BYMONTH' => '2,10',
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('monthly in February and October, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests yearly interpretation.
   */
  public function testYearly() {
    $parts = [
      'FREQ' => 'YEARLY',
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Pacific/Honolulu')),
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    $interpretation = $interpreter->interpret($rules, 'en');
    $this->assertEquals('yearly, starting from Mon, 16 Jul 2012 00:00:00 +1000, forever', $interpretation);
  }

  /**
   * Tests output is in the same time zone as requested.
   */
  public function testDisplayTimeZone() {
    $parts = [
      'FREQ' => 'WEEKLY',
      // Africa/Tripoli: UTC+2 No DST.
      'DTSTART' => new \DateTime('4am 15 July 2012', new \DateTimeZone('Africa/Tripoli')),
    ];
    $rules[] = new RlDateRecurRule($parts);
    $configuration = ['date_format' => $this->randomMachineName()];
    $interpreter = RlInterpreter::create($this->testContainer, $configuration, '', []);
    // Asia/Singapore: UTC+8 No DST.
    $displayTimeZone = new \DateTimeZone('Asia/Singapore');
    $interpretation = $interpreter->interpret($rules, 'en', $displayTimeZone);
    $this->assertEquals('weekly, starting from Sun, 15 Jul 2012 10:00:00 +0800, forever', $interpretation);
  }

}
