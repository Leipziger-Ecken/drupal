<?php

namespace Drupal\rules\TypedData\Type;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * A typed data definition class for describing global site information.
 */
class SiteDataDefinition extends ComplexDataDefinitionBase implements SiteDataDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $this->propertyDefinitions['url'] = DataDefinition::create('uri')
        ->setLabel('URL')
        ->setDescription("The URL of the site's front page.")
        ->setRequired(TRUE);
      $this->propertyDefinitions['login-url'] = DataDefinition::create('uri')
        ->setLabel('Login page')
        ->setDescription("The URL of the site's login page.")
        ->setRequired(TRUE);
      $this->propertyDefinitions['name'] = DataDefinition::create('string')
        ->setLabel('Name')
        ->setDescription('The name of the site.')
        ->setRequired(TRUE);
      $this->propertyDefinitions['slogan'] = DataDefinition::create('string')
        ->setLabel('Slogan')
        ->setDescription('The slogan of the site.')
        ->setRequired(TRUE);
      $this->propertyDefinitions['mail'] = DataDefinition::create('email')
        ->setLabel('Email')
        ->setDescription('The administrative email address for the site.')
        ->setRequired(TRUE);
    }
    return $this->propertyDefinitions;
  }

}
