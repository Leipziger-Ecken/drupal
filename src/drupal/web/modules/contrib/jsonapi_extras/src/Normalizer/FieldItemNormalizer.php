<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\Normalizer\FieldItemNormalizer as JsonapiFieldItemNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager;
use Shaper\Util\Context;

/**
 * Converts the Drupal field structure to a JSON:API array structure.
 */
class FieldItemNormalizer extends JsonApiNormalizerDecoratorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field enhancer manager.
   *
   * @var \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager
   */
  protected $enhancerManager;

  /**
   * Constructs a new FieldItemNormalizer.
   *
   * @param \Drupal\jsonapi\Normalizer\FieldItemNormalizer $inner
   *   The JSON:API field normalizer entity.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerManager $enhancer_manager
   *   The field enhancer manager.
   */
  public function __construct(JsonapiFieldItemNormalizer $inner, EntityTypeManagerInterface $entity_type_manager, ResourceFieldEnhancerManager $enhancer_manager) {
    parent::__construct($inner);
    $this->entityTypeManager = $entity_type_manager;
    $this->enhancerManager = $enhancer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // First get the regular output.
    $normalized_output = parent::normalize($object, $format, $context);
    // Then detect if there is any enhancer to be applied here.
    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type */
    $resource_type = $context['resource_object']->getResourceType();
    $enhancer = $resource_type->getFieldEnhancer($object->getParent()->getName());
    if (!$enhancer) {
      return $normalized_output;
    }
    // Apply any enhancements necessary.
    $context['field_item_object'] = $object;
    $processed = $enhancer->undoTransform($normalized_output->getNormalization(), new Context($context));
    $cacheability = CacheableMetadata::createFromObject($normalized_output)
      ->addCacheTags(['config:jsonapi_resource_config_list']);
    $normalized_output = new CacheableNormalization($cacheability, $processed);

    return $normalized_output;
  }

}
