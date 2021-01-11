<?php

namespace Drupal\rules\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rules\Core\RulesEventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage handler for reaction rule config entities.
 *
 * @todo Create an interface for this.
 */
class ReactionRuleStorage extends ConfigEntityStorage {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * The Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * The event manager.
   *
   * @var \Drupal\rules\Core\RulesEventManager
   */
  protected $eventManager;

  /**
   * Constructs a ReactionRuleStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state_service
   *   The state service.
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The Drupal kernel.
   * @param \Drupal\rules\Core\RulesEventManager $event_manager
   *   The Rules event manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, StateInterface $state_service, DrupalKernelInterface $drupal_kernel, RulesEventManager $event_manager, MemoryCacheInterface $memory_cache = NULL) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);

    $this->stateService = $state_service;
    $this->drupalKernel = $drupal_kernel;
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('state'),
      $container->get('kernel'),
      $container->get('plugin.manager.rules_event'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * Returns a list of event names that are used by active reaction rules.
   *
   * @return string[]
   *   The list of event names keyed by event name.
   */
  protected function getRegisteredEvents() {
    $events = [];
    // 'status' == TRUE when the reaction rule is active.
    foreach ($this->loadByProperties(['status' => TRUE]) as $rules_config) {
      foreach ($rules_config->getEventNames() as $event_name) {
        $event_name = $this->eventManager->getEventBaseName($event_name);
        if (!isset($events[$event_name])) {
          $events[$event_name] = $event_name;
        }
      }
    }
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    // We need to get the registered events before the rule is saved, in order
    // to be able to check afterwards if we need to rebuild the container or
    // not.
    $events_before = $this->getRegisteredEvents();
    $return = parent::save($entity);
    $events_after = $this->getRegisteredEvents();

    // Update the state of registered events.
    $this->stateService->set('rules.registered_events', $events_after);

    // After the reaction rule is saved, we may need to rebuild the container,
    // otherwise the reaction rule will not fire. However, we can do an
    // optimization: Only rebuild the container if there is a new event which
    // was not already registered before. Similarly if the rule is being
    // disabled and there are no other active rules with this event, then also
    // rebuild the container.
    foreach ($entity->getEventNames() as $event_name) {
      if (empty($events_before[$event_name]) || empty($events_after[$event_name])) {
        $this->drupalKernel->rebuildContainer();
        break;
      }
    }

    // When a reaction rule is saved (either added, updated or enabled/disabled)
    // the cache for its event(s) needs to be invalidated. These tags are set in
    // RulesComponentRepository::getMultiple()
    Cache::invalidateTags($entity->getEventNames());

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    // When a rule is deleted the cache for its event(s) must be invalidated.
    foreach ($entities as $entity) {
      Cache::invalidateTags($entity->getEventNames());
    }

    // After deleting a set of reaction rules, sometimes we may need to rebuild
    // the container, to clean it up, so that the generic subscriber is not
    // registered in the container for the rule events which we do not use
    // anymore. So we do that if there is any change in the registered events,
    // after the reaction rules are deleted.
    $events_before = $this->getRegisteredEvents();
    $return = parent::delete($entities);
    $events_after = $this->getRegisteredEvents();

    // Update the state of registered events and rebuild the container.
    if ($events_before != $events_after) {
      $this->stateService->set('rules.registered_events', $events_after);
      $this->drupalKernel->rebuildContainer();
    }

    return $return;
  }

}
