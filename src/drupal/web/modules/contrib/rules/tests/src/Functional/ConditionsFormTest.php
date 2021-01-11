<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Condition can be editted.
 *
 * @group RulesUi
 */
class ConditionsFormTest extends RulesBrowserTestBase {

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
   * Test each condition provided by Rules.
   *
   * Check that every condition can be added to a rule and that the edit page
   * can be accessed. This ensures that the datatypes used in the definitions
   * do exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataConditionsFormWidgets()
   */
  public function testConditionsFormWidgets($id, $settings) {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule.
    $rule = $expressionManager->createRule();
    // Add the condition to the rule.
    $condition = $expressionManager->createCondition($id);
    $rule->addExpressionObject($condition);
    // Save the configuration.
    $expr_id = 'test_condition_' . $id;
    $config_entity = $storage->create([
      'id' => $expr_id,
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();
    // Edit the condition and check that the page is generated without error.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/' . $expr_id . '/edit/' . $condition->getUuid());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit ' . $condition->getLabel());
  }

  /**
   * Provides data for testConditionsFormWidgets().
   *
   * @return array
   *   The test data.
   */
  public function dataConditionsFormWidgets() {
    return [
      ['rules_data_comparison', [
        'widgets' => [
          'data' => 'text-input',
          'operation' => 'text-input',
          'value' => 'text-input',
        ],
      ],
      ],
      ['rules_data_is_empty', []],
      ['rules_list_contains', [
        'widgets' => [
          'list' => 'textarea',
        ],
      ],
      ],
      ['rules_list_count_is', []],
      ['rules_entity_has_field', []],
      ['rules_entity_is_new', []],
      ['rules_entity_is_of_bundle', []],
      ['rules_entity_is_of_type', []],
      ['rules_node_is_of_type', []],
      ['rules_node_is_promoted', []],
      ['rules_node_is_published', []],
      ['rules_node_is_sticky', []],
      ['rules_path_alias_exists', []],
      ['rules_path_has_alias', []],
      ['rules_text_comparison', []],
      ['rules_entity_field_access', []],
      ['rules_user_has_role', []],
      ['rules_user_is_blocked', []],
      ['rules_ip_is_banned', []],
    ];
  }

}
