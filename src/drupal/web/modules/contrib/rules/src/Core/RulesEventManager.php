<?php

namespace Drupal\rules\Core;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\rules\Context\ContextDefinition;

/**
 * Plugin manager for Rules events that can be triggered.
 *
 * Rules events are primarily defined in *.rules.events.yml files.
 *
 * @see \Drupal\rules\Core\RulesEventInterface
 */
class RulesEventManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {
  use CategorizingPluginManagerTrait;

  /**
   * Provides some default values for the definition of all Rules event plugins.
   *
   * @var array
   */
  protected $defaults = [
    'class' => RulesDefaultEventHandler::class,
  ];

  /**
   * The entity type bundle information manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity type bundle information manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->alterInfo('rules_event');
    $this->discovery = new ContainerDerivativeDiscoveryDecorator(new YamlDiscovery('rules.events', $module_handler->getModuleDirectories()));
    $this->factory = new ContainerFactory($this, RulesEventHandlerInterface::class);
    $this->moduleHandler = $module_handler;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If a fully qualified event name is passed, be sure to get the base name
    // first.
    $plugin_id = $this->getEventBaseName($plugin_id);
    return parent::createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    // If a fully qualified event name is passed, be sure to get the base name
    // first.
    $base_plugin_id = $this->getEventBaseName($plugin_id);
    $definition = parent::getDefinition($base_plugin_id, $exception_on_invalid);
    if ($base_plugin_id != $plugin_id) {
      $parts = explode('--', $plugin_id, 2);
      $entity_type_id = explode(':', $parts[0], 2);
      $bundles = $this->entityBundleInfo->getBundleInfo($entity_type_id[1]);
      // Replace the event label with the fully-qualified label.
      // @todo This is a pretty terrible way of deriving the qualified label
      // for a context definition. And it breaks translation.
      $definition['label'] = $definition['label'] . " of type " . $bundles[$parts[1]]['label'];
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (!isset($definition['context_definitions'])) {
      $definition['context_definitions'] = [];
    }
    // Convert the flat context_definitions arrays to ContextDefinition objects.
    foreach ($definition['context_definitions'] as $context_name => $values) {
      $definition['context_definitions'][$context_name] = ContextDefinition::createFromArray($values);
    }
  }

  /**
   * Gets the base name of a configured event name.
   *
   * For a configured event name like {EVENT_NAME}--{SUFFIX}, the base event
   * name {EVENT_NAME} is returned.
   *
   * @return string
   *   The event base name.
   *
   * @see \Drupal\rules\Core\RulesConfigurableEventHandlerInterface::getEventNameSuffix()
   */
  public function getEventBaseName($event_name) {
    // Cut off any suffix from a configured event name.
    if (strpos($event_name, '--') !== FALSE) {
      $parts = explode('--', $event_name, 2);
      return $parts[0];
    }
    return $event_name;
  }

}
