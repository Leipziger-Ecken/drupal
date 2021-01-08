<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\schemata_json_schema\Normalizer\jsonapi\SchemataSchemaNormalizer as SchemataJsonSchemaSchemataSchemaNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * Applies JSONAPI Extras attribute overrides to entity schemas.
 */
class SchemataSchemaNormalizer extends SchemataJsonSchemaSchemataSchemaNormalizer {

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * Constructs a SchemataSchemaNormalizer object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepository $resource_type_repository
   *   A resource repository.
   */
  public function __construct(ResourceTypeRepository $resource_type_repository) {
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized = parent::normalize($entity, $format, $context);

    // Load the resource type for this entity type and bundle.
    $bundle = $entity->getBundleId();
    $bundle = $bundle ?: $entity->getEntityTypeId();
    $resource_type = $this->resourceTypeRepository->get(
      $entity->getEntityTypeId(),
      $bundle
    );

    if (!$resource_type) {
      return $normalized;
    }

    // Alter the attributes according to the resource config.
    if (!empty($normalized['definitions'])) {
      $root = &$normalized['definitions'];
    }
    else {
      $root = &$normalized['properties']['data']['properties'];
    }
    foreach (['attributes', 'relationships'] as $property_type) {
      if (!isset($root[$property_type]['required'])) {
        $root[$property_type]['required'] = [];
      }
      $required_fields = [];
      $properties = NestedArray::getValue($root, [$property_type, 'properties']);
      $properties = $properties ?: [];
      foreach ($properties as $fieldname => $schema) {
        unset($properties[$fieldname]);

        if (!$resource_type->isFieldEnabled($fieldname)) {
          // If the field is disabled, do nothing after removal.
          continue;
        }
        else {
          // Otherwise, substitute the public name.
          $public_name = $resource_type->getPublicName($fieldname);
          $properties[$public_name] = $schema;
          if (in_array($fieldname, $root[$property_type]['required'])) {
            $required_fields[] = $public_name;
          }
        }
      }
      $root[$property_type]['required'] = $required_fields;
    }

    return $normalized;
  }

}
