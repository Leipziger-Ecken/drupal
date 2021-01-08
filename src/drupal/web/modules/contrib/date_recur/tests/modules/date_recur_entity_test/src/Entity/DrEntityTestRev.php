<?php

namespace Drupal\date_recur_entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines the revisionable date recur test entity.
 *
 * @ContentEntityType(
 *   id = "dr_entity_test_rev",
 *   label = @Translation("Date Recur Test revisionable entity"),
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
 *   base_table = "dr_entity_test_rev",
 *   revision_table = "dr_entity_test_rev_revision",
 *   admin_permission = "administer entity_test content",
 *   show_revision_ui = TRUE,
 *   persistent_cache = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/dr_entity_test_rev/{dr_entity_test_rev}",
 *     "add-form" = "/dr_entity_test_rev/add",
 *     "edit-form" = "/dr_entity_test_rev/manage/{dr_entity_test_rev}/edit",
 *     "delete-form" = "/dr_entity_test_rev/delete/entity_test/{dr_entity_test_rev}",
 *     "revision" = "/dr_entity_test_rev/{dr_entity_test_rev}/revision/{dr_entity_test_rev_revision}/view",
 *   },
 *   field_ui_base_route = "entity.dr_entity_test_rev.admin_form",
 * )
 */
class DrEntityTestRev extends EntityTestRev {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['dr'] = BaseFieldDefinition::create('date_recur')
      ->setLabel(t('Rule'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings([
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ]);

    return $fields;
  }

}
