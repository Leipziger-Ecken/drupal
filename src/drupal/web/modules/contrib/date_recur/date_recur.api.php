<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * API Examples for Recurring Dates Field.
 */

declare(strict_types = 1);

/**
 * @file
 * Contains hooks and event examples for date_recur module.
 *
 * This file documents events dispatched by date_recur.
 */

/**
 * Event subscribers for Recurring Date Field.
 *
 * Define a service, e.g:
 * <code>
 * ```yaml
 *  my_module.my_event_subscriber:
 *    class: Drupal\my_module\EventSubscriber\MyEventSubscriber
 *    tags:
 *     - { name: event_subscriber }
 * ```
 * </code>
 */
class MyEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  /**
   * Dispatched after an entity containing a date recur field is saved.
   *
   * @param \Drupal\date_recur\Event\DateRecurValueEvent $event
   *   The date recur value event.
   *
   * @see \Drupal\date_recur\Event\DateRecurEvents::FIELD_VALUE_SAVE
   *   Event name.
   * @see \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList::postSave
   *   Dispatched by.
   * @see \Drupal\date_recur\DateRecurOccurrences::onSave
   *   Live example.
   */
  public function onSave(\Drupal\date_recur\Event\DateRecurValueEvent $event): void {}

  /**
   * Dispatched when an entity containing date recur fields is almost deleted.
   *
   * @param \Drupal\date_recur\Event\DateRecurValueEvent $event
   *   The date recur value event.
   *
   * @see \Drupal\date_recur\Event\DateRecurEvents::FIELD_ENTITY_DELETE
   *   Event name.
   * @see \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList::delete
   *   Dispatched by.
   * @see \Drupal\date_recur\DateRecurOccurrences::onEntityDelete
   *   Live example.
   */
  public function onEntityDelete(\Drupal\date_recur\Event\DateRecurValueEvent $event): void {}

  /**
   * Dispatched when an entity revision is deleted.
   *
   * @param \Drupal\date_recur\Event\DateRecurValueEvent $event
   *   The date recur value event.
   *
   * @see \Drupal\date_recur\Event\DateRecurEvents::FIELD_REVISION_DELETE
   *   Event name.
   * @see \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList::deleteRevision
   *   Dispatched by.
   * @see \Drupal\date_recur\DateRecurOccurrences::onEntityRevisionDelete
   *   Live example.
   */
  public function onEntityRevisionDelete(\Drupal\date_recur\Event\DateRecurValueEvent $event): void {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      \Drupal\date_recur\Event\DateRecurEvents::FIELD_VALUE_SAVE => ['onSave'],
      \Drupal\date_recur\Event\DateRecurEvents::FIELD_ENTITY_DELETE => ['onEntityDelete'],
      \Drupal\date_recur\Event\DateRecurEvents::FIELD_REVISION_DELETE => ['onEntityRevisionDelete'],
    ];
  }

}
