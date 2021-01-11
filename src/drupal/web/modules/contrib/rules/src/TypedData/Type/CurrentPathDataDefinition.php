<?php

namespace Drupal\rules\TypedData\Type;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * A typed data definition class for describing current_path data type.
 */
class CurrentPathDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions['path'] = DataDefinition::create('string')
        ->setLabel('Path')
        ->setDescription('The current path.')
        ->setRequired(TRUE);
      $this->propertyDefinitions['url'] = DataDefinition::create('uri')
        ->setLabel('URL')
        ->setDescription('The current URL.')
        ->setRequired(TRUE);
    }
    return $this->propertyDefinitions;
  }

}
