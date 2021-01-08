<?php

namespace Drupal\Tests\date_recur\Unit;

use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\Exception\DateRecurRulePartIncompatible;
use Drupal\Tests\UnitTestCase;

/**
 * Tests part grid class.
 *
 * @coversDefaultClass \Drupal\date_recur\DateRecurPartGrid
 * @group date_recur
 */
class DateRecurPartGridUnitTest extends UnitTestCase {

  /**
   * Tests a part grid object without making changes to it.
   *
   * @covers ::isAllowEverything
   */
  public function testOriginal() {
    $partGrid = $this->createPartGrid();
    $this->assertTrue($partGrid->isAllowEverything());
    // Test a random frequency.
    $this->assertTrue($partGrid->isFrequencyAllowed('WEEKLY'));
    // Test a random frequency and part.
    $this->assertTrue($partGrid->isPartAllowed('DAILY', 'BYMONTH'));
  }

  /**
   * Tests a part grid object without making changes to it.
   *
   * @covers ::isPartAllowed
   */
  public function testAllowParts() {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', ['BYSETPOS']);

    $this->assertFalse($partGrid->isAllowEverything());

    // Test frequencies.
    $this->assertTrue($partGrid->isFrequencyAllowed('DAILY'));
    $this->assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));

    // Test frequencies and parts.
    $this->assertTrue($partGrid->isPartAllowed('DAILY', 'BYSETPOS'));
    $this->assertFalse($partGrid->isPartAllowed('DAILY', 'BYMONTH'));
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridOriginal() {
    $parts = [];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    $this->assertTrue($partGrid->isAllowEverything());
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllowEverything() {
    $parts = ['all' => TRUE];
    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    $this->assertTrue($partGrid->isAllowEverything());

    // A false 'all' config doesn't disallow everything, it defers part
    // allowance to 'frequency' config.
    $parts = ['all' => FALSE];
    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    $this->assertTrue($partGrid->isAllowEverything());
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllFrequenciesDisabled() {
    $parts = [
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => [],
      ],
    ];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    // Test defined frequency.
    $this->assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));
    // Test undefined frequency.
    $this->assertFalse($partGrid->isFrequencyAllowed('DAILY'));
  }

  /**
   * Tests config settings to grid helper.
   *
   * @covers ::configSettingsToGrid
   */
  public function testSettingsToGridAllPartsForFrequencyAllowed() {
    $parts = [
      'all' => FALSE,
      'frequencies' => [
        'WEEKLY' => [],
        'MONTHLY' => ['*', 'BYSETPOS'],
      ],
    ];

    $partGrid = DateRecurPartGrid::configSettingsToGrid($parts);
    // Test defined frequency no parts.
    $this->assertFalse($partGrid->isFrequencyAllowed('WEEKLY'));
    $this->assertFalse($partGrid->isPartAllowed('WEEKLY', 'BYSETPOS'));
    // Test undefined frequency.
    $this->assertFalse($partGrid->isFrequencyAllowed('DAILY'));
    $this->assertFalse($partGrid->isPartAllowed('DAILY', 'BYSETPOS'));
    // Test defined frequency.
    $this->assertTrue($partGrid->isFrequencyAllowed('MONTHLY'));
    // Test defined part.
    $this->assertTrue($partGrid->isPartAllowed('MONTHLY', 'BYSETPOS'));
    // Test undefined frequency.
    $this->assertTrue($partGrid->isPartAllowed('MONTHLY', 'BYMONTH'));
  }

  /**
   * Tests config settings to grid helper with a part incompatible with a freq.
   *
   * @covers ::configSettingsToGrid
   */
  public function testIncompatiblePartException() {
    $partGrid = $this->createPartGrid();
    $partGrid->allowParts('DAILY', ['*']);
    // BYWEEKNO is incompatible with daily.
    $this->setExpectedException(DateRecurRulePartIncompatible::class);
    $partGrid->isPartAllowed('DAILY', 'BYWEEKNO');
  }

  /**
   * Create a new part grid.
   *
   * @return \Drupal\date_recur\DateRecurPartGrid
   *   New part grid object.
   */
  protected function createPartGrid() {
    return new DateRecurPartGrid();
  }

}
