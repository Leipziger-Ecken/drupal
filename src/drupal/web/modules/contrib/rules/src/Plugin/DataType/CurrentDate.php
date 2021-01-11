<?php

namespace Drupal\rules\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * The "current_date" data type.
 *
 * The "map" data type represent a simple complex data type, e.g. for
 * representing associative arrays. It can also serve as base class for any
 * complex data type.
 *
 * By default there is no metadata for contained properties. Extending classes
 * may want to override MapDataDefinition::getPropertyDefinitions() to define
 * it.
 *
 * @ingroup typed_data
 *
 * @DataType(
 *   id = "current_date",
 *   label = @Translation("Current date"),
 *   description = @Translation("Current date"),
 *   definition_class = "\Drupal\rules\TypedData\Type\CurrentDateDataDefinition"
 * )
 */
class CurrentDate extends Map {

  /**
   * Sets the data values for this type.
   *
   * Overrides \Drupal\Core\TypedData\TypedData::setValue().
   *
   * @param array|null $values
   *   An array of property values.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults
   *   to TRUE. If a property is updated from a parent object, set it to FALSE
   *   to avoid being notified again.
   */
  public function setValue($values, $notify = TRUE) {
    // @todo Should check if is IteratorAggregate instead, then we can
    // use foreach and treat arrays/objects the same.
    if (isset($values) && !is_array($values)) {
      if (!method_exists($values, 'toArray')) {
        throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
      }
      else {
        $this->values = $values->toArray();
      }
    }
    else {
      $this->values = $values;
    }

    // Update any existing property objects.
    foreach ($this->properties as $name => $property) {
      $value = isset($values[$name]) ? $values[$name] : NULL;
      $property->setValue($value, FALSE);
      // Remove the value from $this->values to ensure it does not contain any
      // value for computed properties.
      unset($this->values[$name]);
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
