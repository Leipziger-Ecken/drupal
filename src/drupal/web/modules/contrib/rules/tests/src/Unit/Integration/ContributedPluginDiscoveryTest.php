<?php

namespace Drupal\Tests\rules\Unit\Integration;

/**
 * Tests contributed plugin discovery by RulesIntegrationTestBase.
 *
 * Ensures that Rules plugins defined by contributed (and test) modules may
 * be found by RulesIntegrationTestBase.
 *
 * @coversDefaultClass \Drupal\rules_test\Plugin\Condition\TestConditionTrue
 * @group RulesCondition
 */
class ContributedPluginDiscoveryTest extends RulesEntityIntegrationTestBase {

  /**
   * The condition to be tested.
   *
   * @var \Drupal\rules\Core\RulesConditionInterface
   */
  protected $condition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableModule('rules_test');
    $this->condition = $this->conditionManager->createInstance('rules_test_true');
  }

  /**
   * Tests evaluating a condition provided by the 'rules_test' module.
   *
   * We're trying to ensure that plugins from contributed modules are found in
   * the namespace established by the RulesIntegrationTestBase::enableModule().
   *
   * @covers ::evaluate
   */
  public function testPluginDiscovery() {
    $this->assertTrue($this->condition->evaluate());
  }

}
