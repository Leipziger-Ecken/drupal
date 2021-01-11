<?php

namespace Drupal\rules\Core;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Plugin\PluginBase;

/**
 * Default event handler class.
 */
class RulesDefaultEventHandler extends PluginBase implements RulesEventHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function getContextDefinitions() {
    $definition = $this->getPluginDefinition();
    if ($this instanceof RulesConfigurableEventHandlerInterface) {
      $this->refineContextDefinitions();
    }
    return !empty($definition['context_definitions']) ? $definition['context_definitions'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition($name) {
    $definitions = $this->getContextDefinitions();
    if (empty($definitions[$name])) {
      throw new ContextException(sprintf("The context '%s' is not a valid context.", $name));
    }
    return $definitions[$name];
  }

}
