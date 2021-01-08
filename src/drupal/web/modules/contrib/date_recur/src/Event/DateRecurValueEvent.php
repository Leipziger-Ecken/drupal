<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Event;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched when an entity containing a date recur field is modified.
 */
class DateRecurValueEvent extends Event {

  /**
   * The field list.
   *
   * @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList
   */
  protected $field;

  /**
   * Whether the entity was created.
   *
   * @var bool
   */
  protected $insert;

  /**
   * DateRecurValueEvent constructor.
   *
   * @param \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $field
   *   The date recur field item list.
   * @param bool $insert
   *   Specifies whether the entity was created.
   */
  public function __construct(DateRecurFieldItemList $field, $insert) {
    $this->field = $field;
    $this->insert = $insert;
  }

  /**
   * Get the field list.
   *
   * The field cannot be changed because the entity has already been saved.
   *
   * @return \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList
   *   The date recur field item list.
   */
  public function getField(): DateRecurFieldItemList {
    return $this->field;
  }

  /**
   * Get whether the entity was created.
   *
   * @return bool
   *   Whether the entity was created.
   */
  public function isInsert(): bool {
    return $this->insert;
  }

}
