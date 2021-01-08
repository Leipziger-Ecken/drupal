<?php

namespace Drupal\date_recur_entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a test entity with single cardinality date recur base field.
 *
 * @ContentEntityType(
 *   id = "dr_entity_test_single",
 *   label = @Translation("Date Recur Test (single cardinality) entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_test\EntityTestListBuilder",
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\entity_test\EntityTestViewsData"
 *   },
 *   base_table = "dr_entity_test_single",
 *   admin_permission = "administer entity_test content",
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/dr_entity_test_single/{dr_entity_test_single}",
 *     "add-form" = "/dr_entity_test_single/add",
 *     "edit-form" = "/dr_entity_test_single/manage/{dr_entity_test_single}/edit",
 *     "delete-form" = "/dr_entity_test_single/delete/entity_test/{dr_entity_test_single}",
 *   },
 *   field_ui_base_route = "entity.dr_entity_test_single.admin_form",
 * )
 */
class DrEntityTestSingleCardinality extends DrEntityTest {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['dr']->setCardinality(1);

    return $fields;
  }

}
