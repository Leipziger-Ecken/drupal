<?php

namespace Drupal\Tests\rules\Unit\Integration\RulesAction;

use Drupal\node\NodeInterface;
use Drupal\Tests\rules\Unit\Integration\RulesEntityIntegrationTestBase;

/**
 * @coversDefaultClass \Drupal\rules\Plugin\RulesAction\NodeMakeUnsticky
 * @group RulesAction
 */
class NodeMakeUnstickyTest extends RulesEntityIntegrationTestBase {

  /**
   * The action to be tested.
   *
   * @var \Drupal\rules\Core\RulesActionInterface
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->action = $this->actionManager->createInstance('rules_node_make_unsticky');
  }

  /**
   * Tests the summary.
   *
   * @covers ::summary
   */
  public function testSummary() {
    $this->assertEquals('Make selected content not sticky', $this->action->summary());
  }

  /**
   * Tests the action execution.
   *
   * @covers ::execute
   */
  public function testActionExecution() {
    $node = $this->prophesizeEntity(NodeInterface::class);
    $node->setSticky(NodeInterface::NOT_STICKY)->shouldBeCalledTimes(1);

    $this->action->setContextValue('node', $node->reveal());
    $this->action->execute();

    $this->assertEquals(
      ['node'],
      $this->action->autoSaveContext(),
      'Action returns the user context name for auto saving.'
    );
  }

}
