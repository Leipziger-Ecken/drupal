<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Action can be editted.
 *
 * @group RulesUi
 */
class ActionsFormTest extends RulesBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'ban', 'rules', 'typed_data'];

  /**
   * We use the minimal profile because we want to test local action links.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * A user account with administration permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser([
      'administer rules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Test each action provided by Rules.
   *
   * Check that every action can be added to a rule and that the edit page can
   * be accessed. This ensures that the datatypes used in the definitions do
   * exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataActionsFormWidgets()
   */
  public function testActionsFormWidgets($id, $settings) {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule.
    $rule = $expressionManager->createRule();
    // Add the action to the rule.
    $action = $expressionManager->createAction($id);
    $rule->addExpressionObject($action);
    // Save the configuration.
    $expr_id = 'test_action_' . str_replace(':', '_', $id);
    $config_entity = $storage->create([
      'id' => $expr_id,
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();
    // Edit the action and check that the page is generated without error.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/' . $expr_id . '/edit/' . $action->getUuid());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit ' . $action->getLabel());
  }

  /**
   * Provides data for testActionsFormWidgets().
   *
   * @return array
   *   The test data.
   */
  public function dataActionsFormWidgets() {
    return [
      ['rules_data_calculate_value', [
        'widgets' => [
          'input-1' => 'text-input',
          'operator' => 'text-input',
          'input-2' => 'text-input',
        ],
      ],
      ],
      ['rules_data_convert', []],
      ['rules_list_item_add', []],
      ['rules_list_item_remove', []],
      ['rules_data_set', []],
      ['rules_entity_create:node', []],
      ['rules_entity_create:user', []],
      ['rules_entity_delete', []],
      ['rules_entity_fetch_by_field', []],
      ['rules_entity_fetch_by_id', []],
      ['rules_entity_path_alias_create:entity:node', []],
      ['rules_entity_save', []],
      ['rules_node_make_sticky', []],
      ['rules_node_make_unsticky', []],
      ['rules_node_publish', []],
      ['rules_node_unpublish', []],
      ['rules_node_promote', []],
      ['rules_node_unpromote', []],
      ['rules_path_alias_create', []],
      ['rules_path_alias_delete_by_alias', []],
      ['rules_path_alias_delete_by_path', []],
      ['rules_send_account_email', []],
      ['rules_email_to_users_of_role', [
        'widgets' => [
          'message' => 'textarea',
        ],
      ],
      ],
      ['rules_system_message', []],
      ['rules_page_redirect', []],
      ['rules_send_email', [
        'widgets' => [
          'message' => 'textarea',
        ],
      ],
      ],
      ['rules_user_block', []],
      ['rules_user_role_add', []],
      ['rules_user_role_remove', []],
      ['rules_user_unblock', []],
      ['rules_variable_add', []],
      ['rules_ban_ip', []],
      ['rules_unban_ip', []],
    ];
  }

}
