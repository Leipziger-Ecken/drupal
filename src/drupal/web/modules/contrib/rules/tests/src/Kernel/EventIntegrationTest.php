<?php

namespace Drupal\Tests\rules\Kernel;

use Drupal\rules\Context\ContextConfig;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test for the Symfony event mapping to Rules events.
 *
 * @group RulesEvent
 */
class EventIntegrationTest extends RulesKernelTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field', 'node', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test that the user login hook triggers the Rules event listener.
   */
  public function testUserLoginEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log',
      ContextConfig::create()
        ->map('message', 'account.name.0.value')
    );

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => 'rules_user_login']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $account = User::create(['name' => 'test_user']);
    // Invoke the hook manually which should trigger the rule.
    rules_user_login($account);

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('test_user');
  }

  /**
   * Test that the user logout hook triggers the Rules event listener.
   */
  public function testUserLogoutEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => 'rules_user_logout']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $account = $this->container->get('current_user');
    // Invoke the hook manually which should trigger the rule.
    rules_user_logout($account);

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Test that the cron hook triggers the Rules event listener.
   */
  public function testCronEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => 'rules_system_cron']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    // Run cron.
    $this->container->get('cron')->run();

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Test that a Logger message triggers the Rules debug logger listener.
   */
  public function testSystemLoggerEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => 'rules_system_logger_event']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    // Creates a logger-item, that must be dispatched as event.
    $this->container->get('logger.factory')->get('rules_test')
      ->notice("This message must get logged and dispatched as rules_system_logger_event");

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Test that Drupal initializing triggers the Rules debug logger listener.
   */
  public function testInitEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => KernelEvents::REQUEST]],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $dispatcher = $this->container->get('event_dispatcher');

    // Remove all the listeners except Rules before triggering an event.
    $listeners = $dispatcher->getListeners(KernelEvents::REQUEST);
    foreach ($listeners as $listener) {
      if (empty($listener[1]) || $listener[1] != 'onRulesEvent') {
        $dispatcher->removeListener(KernelEvents::REQUEST, $listener);
      }
    }
    // Manually trigger the initialization event.
    $dispatcher->dispatch(KernelEvents::REQUEST);

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Test that Drupal terminating triggers the Rules debug logger listener.
   */
  public function testTerminateEvent() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => KernelEvents::TERMINATE]],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $dispatcher = $this->container->get('event_dispatcher');

    // Remove all the listeners except Rules before triggering an event.
    $listeners = $dispatcher->getListeners(KernelEvents::TERMINATE);
    foreach ($listeners as $listener) {
      if (empty($listener[1]) || $listener[1] != 'onRulesEvent') {
        $dispatcher->removeListener(KernelEvents::TERMINATE, $listener);
      }
    }
    // Manually trigger the initialization event.
    $dispatcher->dispatch(KernelEvents::TERMINATE);

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Test that rules config supports multiple events.
   */
  public function testMultipleEvents() {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ]);
    $config_entity->set('events', [
      ['event_name' => 'rules_user_login'],
      ['event_name' => 'rules_user_logout'],
    ]);
    $config_entity->set('expression', $rule->getConfiguration());
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    $account = User::create(['name' => 'test_user']);
    // Invoke the hook manually which should trigger the rules_user_login event.
    rules_user_login($account);
    // Invoke the hook manually which should trigger the rules_user_logout
    // event.
    rules_user_logout($account);

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
    $this->assertRulesDebugLogEntryExists('action called', 1);
  }

  /**
   * Tests that the entity presave/update events work with original entities.
   *
   * @param string $event_name
   *   The event name that should be configured in the test rule.
   *
   * @dataProvider providerTestEntityOriginal
   */
  public function testEntityOriginal($event_name) {
    // Create a node that we will change and save later.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create([
        'type' => 'page',
        'display_submitted' => FALSE,
      ])
      ->save();

    $node = $entity_type_manager->getStorage('node')
      ->create([
        'title' => 'test',
        'type' => 'page',
      ]);
    $node->save();

    // Create a rule with a condition to compare the changed node title. If the
    // title has changed the action is executed.
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_data_comparison', ContextConfig::create()
      ->map('data', 'node.title.value')
      ->map('value', 'node_unchanged.title.value')
      ->negateResult()
    );
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => $event_name]],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    // Now change the title and trigger the presave event by saving the node.
    $node->setTitle('new title');
    $node->save();

    $this->assertRulesDebugLogEntryExists('action called');
  }

  /**
   * Provides test data for testEntityOriginal().
   */
  public function providerTestEntityOriginal() {
    return [
      ['rules_entity_presave:node'],
      ['rules_entity_update:node'],
    ];
  }

  /**
   * Tests that entity events are fired for the correct bundle.
   */
  public function testBundleQualifiedEvents() {
    // Create an article node type and a page node type.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')->create([
      'type' => 'article',
      'title' => 'Article',
    ])->save();
    $entity_type_manager->getStorage('node_type')->create([
      'type' => 'page',
      'title' => 'Page',
    ])->save();

    // Create a rule to fire when a new article is created.
    $rule = $this->expressionManager->createRule();
    $rule->addAction('rules_test_debug_log',
      ContextConfig::create()
        ->map('message', 'node.title.value')
    );

    // Create a rule to fire when a new page is created.
    $rule = $this->expressionManager->createRule();
    $rule->addAction('rules_test_debug_log',
      ContextConfig::create()
        ->map('message', 'node.title.value')
    );

    $config_entity = $this->storage->create([
      'id' => 'test_article_rule',
      'events' => [['event_name' => 'rules_entity_insert:node--article']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    $config_entity = $this->storage->create([
      'id' => 'test_page_rule',
      'events' => [['event_name' => 'rules_entity_insert:node--page']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    // Create a page - this should dispatch a
    // "rules_entity_insert:node--page" event.
    $node = $entity_type_manager->getStorage('node')->create([
      'title' => 'Test page entity bundle event',
      'type' => 'page',
    ]);
    $node->save();

    // Only the rule "test_page_rule" should fire.
    $this->assertRulesDebugLogEntryExists('Test page entity bundle event');
    $this->assertRulesDebugLogEntryNotExists('Test article entity bundle event');
  }

}
