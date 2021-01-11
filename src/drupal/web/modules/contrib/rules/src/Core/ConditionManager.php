<?php

namespace Drupal\rules\Core;

use Drupal\Core\Condition\ConditionManager as CoreConditionManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\rules\Context\AnnotatedClassDiscovery;

/**
 * Extends the core condition manager to add in Rules' context improvements.
 */
class ConditionManager extends CoreConditionManager {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\rules\Core\RulesConditionInterface|\Drupal\Core\Condition\ConditionInterface
   *   A fully configured plugin instance.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      // Swap out the annotated class discovery used, so we can control the
      // annotation classes picked.
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName);
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    // Make sure that all definitions have a category to avoid PHP notices in
    // CategorizingPluginManagerTrait.
    // @todo Fix this in core in CategorizingPluginManagerTrait.
    foreach ($definitions as $key => &$definition) {
      if (!isset($definition['category'])) {
        $definition['category'] = $this->t('Other');
        // @todo Remove the unset() when core conditions can work as
        // Rules conditions.
        //
        // @see https://www.drupal.org/project/rules/issues/2927132
        //
        // Because core Conditions do not currently define some context values
        // required by Rules, we need to make sure they can't be selected
        // through the Rules UI. To do this, we make use of the fact that none
        // of the core Conditions make use of the concept of 'category' as
        // defined by the CategorizingPluginManager. Thus, we assume that any
        // condition without a 'category' is a core Condition and we remove it
        // from  list of plugin definitions used by the Rules UI.
        unset($definitions[$key]);
      }
    }
    return $definitions;
  }

}
