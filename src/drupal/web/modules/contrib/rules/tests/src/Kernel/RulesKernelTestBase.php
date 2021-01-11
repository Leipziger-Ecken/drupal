<?php

namespace Drupal\Tests\rules\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Rules Drupal unit tests.
 */
abstract class RulesKernelTestBase extends KernelTestBase {

  /**
   * The expression plugin manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManager
   */
  protected $expressionManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Rules debug logger channel.
   *
   * @var \Drupal\rules\Logger\RulesDebugLoggerChannel
   */
  protected $logger;

  /**
   * Rules debug log.
   *
   * @var \Drupal\rules\Logger\RulesDebugLog
   */
  protected $debugLog;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'rules',
    'rules_test',
    'system',
    'typed_data',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->debugLog = $this->container->get('logger.rules_debug_log');

    // Turn on debug logging, set error level to collect only errors. This way
    // we can ignore the normal Rules debug messages that would otherwise get
    // in the way of our tests.
    $config = $this->container->get('config.factory')->getEditable('rules.settings');
    $config
      ->set('debug_log.enabled', TRUE)
      ->set('debug_log.log_level', 'error')
      ->save();

    $this->expressionManager = $this->container->get('plugin.manager.rules_expression');
    $this->conditionManager = $this->container->get('plugin.manager.condition');
    $this->typedDataManager = $this->container->get('typed_data_manager');
    $this->messenger = $this->container->get('messenger');
    $this->time = $this->container->get('datetime.time');
  }

  /**
   * Creates a new condition.
   *
   * @param string $id
   *   The condition plugin id.
   *
   * @return \Drupal\rules\Core\RulesConditionInterface
   *   The created condition plugin.
   */
  protected function createCondition($id) {
    $condition = $this->expressionManager->createInstance('rules_condition', [
      'condition_id' => $id,
    ]);
    return $condition;
  }

  /**
   * Checks if particular message is in the log with given delta.
   *
   * @param string $message
   *   Log message.
   * @param int $log_item_index
   *   Log item's index in log entries stack.
   */
  protected function assertRulesDebugLogEntryExists($message, $log_item_index = 0) {
    // Test that the action has logged something.
    $logs = $this->debugLog->getLogs();
    $this->assertEquals($logs[$log_item_index]['message'], $message);
  }

  /**
   * Checks if particular message is NOT in the log.
   *
   * @param string $message
   *   Log message.
   */
  protected function assertRulesDebugLogEntryNotExists($message) {
    // Check each log entry.
    $logs = $this->debugLog->getLogs();
    foreach ($logs as $log) {
      $this->assertNotEquals($log['message'], $message);
    }
  }

}
