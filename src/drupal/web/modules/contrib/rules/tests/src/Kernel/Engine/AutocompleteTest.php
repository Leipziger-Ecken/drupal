<?php

namespace Drupal\Tests\rules\Kernel\Engine;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rules\Context\ContextDefinition;
use Drupal\rules\Engine\RulesComponent;
use Drupal\Tests\rules\Kernel\RulesKernelTestBase;

/**
 * Tests that data selector autocomplete results work correctly.
 *
 * @group Rules
 */
class AutocompleteTest extends RulesKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'rules', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // The global CurrentUserContext doesn't work properly without a
    // fully-installed user module.
    // @see https://www.drupal.org/project/rules/issues/2989417
    $this->container->get('module_handler')->loadInclude('user', 'install');
    user_install();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    // Create a multi-value integer field for testing.
    FieldStorageConfig::create([
      'field_name' => 'field_integer',
      'type' => 'integer',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_integer',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();
  }

  /**
   * Tests autocompletion works for a variable in the metadata state.
   */
  public function testAutocomplete() {
    $rule = $this->expressionManager->createRule();
    $action = $this->expressionManager->createAction('rules_data_set');
    $rule->addExpressionObject($action);

    $results = RulesComponent::create($rule)
      ->addContextDefinition('entity', ContextDefinition::create('entity'))
      ->autocomplete('e', $action);

    $this->assertSame([
      [
        'value' => 'entity',
        'label' => 'entity',
      ],
      [
        'value' => 'entity.',
        'label' => 'entity...',
      ],
    ], $results);
  }

  /**
   * Test various node example data selectors.
   */
  public function testNodeAutocomplete() {
    $rule = $this->expressionManager->createRule();
    $rule->addAction('rules_data_set');

    $component = RulesComponent::create($rule)
      ->addContextDefinition('node', ContextDefinition::create('entity:node:page'));

    // Tests that "node.uid.en" returns the suggestion "node.uid.entity".
    $results = $component->autocomplete('node.uid.en');
    $this->assertSame([
      [
        'value' => 'node.uid.entity',
        'label' => 'node.uid.entity (User)',
      ],
      [
        'value' => 'node.uid.entity.',
        'label' => 'node.uid.entity... (User)',
      ],
    ], $results);

    // Tests that "node." returns all available fields on a node.
    $results = $component->autocomplete('node.');
    $expected = array_merge([
      [
        'value' => 'node.changed',
        'label' => 'node.changed (Changed)',
      ],
      [
        'value' => 'node.changed.',
        'label' => 'node.changed... (Changed)',
      ],
      [
        'value' => 'node.created',
        'label' => 'node.created (Authored on)',
      ],
      [
        'value' => 'node.created.',
        'label' => 'node.created... (Authored on)',
      ],
      [
        'value' => 'node.default_langcode',
        'label' => 'node.default_langcode (Default translation)',
      ],
      [
        'value' => 'node.default_langcode.',
        'label' => 'node.default_langcode... (Default translation)',
      ],
      [
        'value' => 'node.field_integer',
        'label' => 'node.field_integer (field_integer)',
      ],
      [
        'value' => 'node.field_integer.',
        'label' => 'node.field_integer... (field_integer)',
      ],
      [
        'value' => 'node.langcode',
        'label' => 'node.langcode (Language)',
      ],
      [
        'value' => 'node.langcode.',
        'label' => 'node.langcode... (Language)',
      ],
      [
        'value' => 'node.nid',
        'label' => 'node.nid (ID)',
      ],
      [
        'value' => 'node.nid.',
        'label' => 'node.nid... (ID)',
      ],
      [
        'value' => 'node.promote',
        'label' => 'node.promote (Promoted to front page)',
      ],
      [
        'value' => 'node.promote.',
        'label' => 'node.promote... (Promoted to front page)',
      ],
    ],
    [
      [
        'value' => 'node.revision_default',
        'label' => 'node.revision_default (Default revision)',
      ],
      [
        'value' => 'node.revision_default.',
        'label' => 'node.revision_default... (Default revision)',
      ],
    ],
    [
      [
        'value' => 'node.revision_log',
        'label' => 'node.revision_log (Revision log message)',
      ],
      [
        'value' => 'node.revision_log.',
        'label' => 'node.revision_log... (Revision log message)',
      ],
      [
        'value' => 'node.revision_timestamp',
        'label' => 'node.revision_timestamp (Revision create time)',
      ],
      [
        'value' => 'node.revision_timestamp.',
        'label' => 'node.revision_timestamp... (Revision create time)',
      ],
      [
        'value' => 'node.revision_translation_affected',
        'label' => 'node.revision_translation_affected (Revision translation affected)',
      ],
      [
        'value' => 'node.revision_translation_affected.',
        'label' => 'node.revision_translation_affected... (Revision translation affected)',
      ],
      [
        'value' => 'node.revision_uid',
        'label' => 'node.revision_uid (Revision user)',
      ],
      [
        'value' => 'node.revision_uid.',
        'label' => 'node.revision_uid... (Revision user)',
      ],
      [
        'value' => 'node.status',
        'label' => 'node.status (Published)',
      ],
      [
        'value' => 'node.status.',
        'label' => 'node.status... (Published)',
      ],
      [
        'value' => 'node.sticky',
        'label' => 'node.sticky (Sticky at top of lists)',
      ],
      [
        'value' => 'node.sticky.',
        'label' => 'node.sticky... (Sticky at top of lists)',
      ],
      [
        'value' => 'node.title',
        'label' => 'node.title (Title)',
      ],
      [
        'value' => 'node.title.',
        'label' => 'node.title... (Title)',
      ],
      [
        'value' => 'node.type',
        'label' => 'node.type (Content type)',
      ],
      [
        'value' => 'node.type.',
        'label' => 'node.type... (Content type)',
      ],
      [
        'value' => 'node.uid',
        'label' => 'node.uid (Authored by)',
      ],
      [
        'value' => 'node.uid.',
        'label' => 'node.uid... (Authored by)',
      ],
      [
        'value' => 'node.uuid',
        'label' => 'node.uuid (UUID)',
      ],
      [
        'value' => 'node.uuid.',
        'label' => 'node.uuid... (UUID)',
      ],
      [
        'value' => 'node.vid',
        'label' => 'node.vid (Revision ID)',
      ],
      [
        'value' => 'node.vid.',
        'label' => 'node.vid... (Revision ID)',
      ],
    ]);
    // Because this is a huge array, run the assertion per entry as that is
    // easier for debugging.
    foreach ($expected as $index => $entry) {
      $this->assertSame($entry, $results[$index]);
    }

    // Tests that "node.uid.entity.na" returns "node.uid.entity.name".
    $results = $component->autocomplete('node.uid.entity.na');
    $this->assertSame([
      [
        'value' => 'node.uid.entity.name',
        'label' => 'node.uid.entity.name (Name)',
      ],
      [
        'value' => 'node.uid.entity.name.',
        'label' => 'node.uid.entity.name... (Name)',
      ],
    ], $results);

    // A multi-valued field should show numeric indices suggestions.
    $results = $component->autocomplete('node.field_integer.');
    $this->assertSame([
      [
        'value' => 'node.field_integer.0',
        'label' => 'node.field_integer.0',
      ],
      [
        'value' => 'node.field_integer.0.',
        'label' => 'node.field_integer.0...',
      ],
      [
        'value' => 'node.field_integer.1',
        'label' => 'node.field_integer.1',
      ],
      [
        'value' => 'node.field_integer.1.',
        'label' => 'node.field_integer.1...',
      ],
      [
        'value' => 'node.field_integer.2',
        'label' => 'node.field_integer.2',
      ],
      [
        'value' => 'node.field_integer.2.',
        'label' => 'node.field_integer.2...',
      ],
      [
        'value' => 'node.field_integer.value',
        'label' => 'node.field_integer.value (Integer value)',
      ],
    ], $results);

    // A single-valued field should not show numeric indices suggestions.
    $results = $component->autocomplete('node.title.');
    $this->assertSame([
      [
        'value' => 'node.title.value',
        'label' => 'node.title.value (Text value)',
      ],
    ], $results);

    // A single-valued field should not show numeric indices suggestions.
    $results = $component->autocomplete('n');
    $this->assertSame([
      [
        'value' => 'node',
        'label' => 'node',
      ],
      [
        'value' => 'node.',
        'label' => 'node...',
      ],
    ], $results);
  }

  /**
   * Tests that autocomplete results for a flat list are correct.
   */
  public function testListAutocomplete() {
    $rule = $this->expressionManager->createRule();
    $rule->addAction('rules_data_set');

    $context_definition = ContextDefinition::create('integer');
    $context_definition->setMultiple();
    $component = RulesComponent::create($rule)
      ->addContextDefinition('list', $context_definition);

    $results = $component->autocomplete('list.');
    $this->assertSame([
      [
        'value' => 'list.0',
        'label' => 'list.0',
      ],
      [
        'value' => 'list.1',
        'label' => 'list.1',
      ],
      [
        'value' => 'list.2',
        'label' => 'list.2',
      ],
    ], $results);
  }

}
