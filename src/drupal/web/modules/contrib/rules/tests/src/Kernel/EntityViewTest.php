<?php

namespace Drupal\Tests\rules\Kernel;

/**
 * Tests that rules_entity_view() does not throw fatal errors.
 *
 * @group Rules
 */
class EntityViewTest extends RulesKernelTestBase {

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

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['node']);
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests that rules_entity_view() can be invoked correctly.
   */
  public function testEntityViewHook() {
    // Create a node.
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

    // Build the node render array and render it, so that hook_entity_view() is
    // invoked.
    $view_builder = $entity_type_manager->getViewBuilder('node');
    $build = $view_builder->view($node);
    $this->container->get('renderer')->renderPlain($build);
  }

}
