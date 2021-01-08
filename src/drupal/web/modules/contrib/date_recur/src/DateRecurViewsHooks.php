<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines Views hooks.
 */
class DateRecurViewsHooks implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * DateRecurViewsHooks constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The active database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, TypedDataManagerInterface $typedDataManager) {
    $this->database = $connection;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->typedDataManager = $typedDataManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * Implements hook_views_data().
   *
   * @see \hook_views_data()
   * @see \date_recur_views_data()
   */
  public function viewsData(): array {
    $allFields = $this->getDateRecurFields();
    $data = [];
    foreach ($allFields as $entityTypeId => $fields) {
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      /** @var \Drupal\views\EntityViewsDataInterface $viewsData */
      $viewsData = $this->entityTypeManager->getHandler($entityType->id(), 'views_data');
      $entityViewsTable = $viewsData->getViewsTableForEntityType($entityType);

      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $fields */
      foreach ($fields as $fieldId => $field) {
        $fieldLabel = $this->getFieldLabel($field->getTargetEntityTypeId(), $fieldId);
        $fieldName = $field->getName();
        $entityIdField = $entityType->getKey('id');
        $entityRevisionField = $entityType->isRevisionable() ? $entityType->getKey('revision') : NULL;
        $tArgs = [
          '@field_name' => $fieldLabel,
          '@entity_type' => $entityType->getLabel(),
        ];

        // Occurrence filter.
        $data[$entityViewsTable][$fieldName . '_occurrences']['filter'] = [
          'id' => 'date_recur_occurrences_filter',
          'title' => $this->t('Occurrences filter for @field_name', $tArgs),
          // Instruct the filter to join the occurrence.entity_id field on
          // base.entityId:
          'field base entity_id' => $entityIdField,
          'date recur field name' => $fieldName,
          'entity_type' => $entityType->id(),
        ];

        // Relationship from entity table to occurrence table.
        $occurrenceTableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($field);
        $data[$entityViewsTable][$fieldName . '_occurrences']['relationship'] = [
          'id' => 'standard',
          'base' => $occurrenceTableName,
          'base field' => isset($entityRevisionField) ? 'revision_id' : 'entity_id',
          'help' => $this->t('Get all occurrences for recurring date field @field_name', $tArgs),
          'field' => $entityRevisionField ?? $entityIdField,
          // Add new 'title'.
          'title' => $this->t('Occurrences of @field_name', $tArgs),
          // Default label for relationship in the UI.
          'label' => $this->t('Occurrences of @field_name', $tArgs),
        ];

        $dateField = [
          'id' => 'date_recur_date',
          'source date format' => $this->getFieldDateFormat($field),
          'source time zone' => DateTimeItemInterface::STORAGE_TIMEZONE,
        ];
        $data[$occurrenceTableName][$fieldName . '_value']['field'] = $dateField;
        $data[$occurrenceTableName][$fieldName . '_end_value']['field'] = $dateField;

        // Attached fields get automatic functionality provided by
        // hook_field_views_data(). Add features here for base fields.
        if ($field instanceof BaseFieldDefinition) {
          $data[$occurrenceTableName]['table']['group'] = $this->t('Occurrences for @entity_type @field_name', [
            '@entity_type' => $entityType->getLabel(),
            '@field_name' => $fieldLabel,
          ]);

          $startField = $fieldName . '_value';
          $endField = $fieldName . '_end_value';
          $data[$occurrenceTableName][$startField]['title'] = $this->t('Occurrence start date');
          $data[$occurrenceTableName][$endField]['title'] = $this->t('Occurrence end date');
          // Sort.
          // datetime_range_field_views_data() uses 'datetime', which relies
          // on entity things.
          $data[$occurrenceTableName][$startField]['sort']['id'] = 'date';
          $data[$occurrenceTableName][$endField]['sort']['id'] = 'date';
        }
      }
    }

    return $data;
  }

  /**
   * Implements hook_views_data_alter().
   *
   * @see \hook_views_data_alter()
   * @see \date_recur_views_data_alter()
   */
  public function viewsDataAlter(array &$data): void {
    $removeFieldKeys = $this->getViewsPluginTypes();
    $removeFieldKeys = array_flip($removeFieldKeys);

    // Base fields don't yet have an option to provide defaults for their type,
    // but entity views data still tries to add default views integration for
    // the field primitives in
    // \Drupal\views\EntityViewsData::mapSingleFieldViewsData
    // Feature to add in: https://www.drupal.org/node/2489476.
    // Remove the default plugins from entity views data since they are not
    // something that should be supported. This also means adding plugins for
    // date recur base fields cannot be added in hook_views_data or
    // entity 'views_data' handlers..
    // @todo bring in all plugins from \datetime_range_field_views_data() if/when
    // it supports base fields.
    $allFields = $this->getDateRecurFields();
    foreach ($allFields as $entityTypeId => $fields) {
      /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entityStorage */
      $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $tableMapping */
      $tableMapping = $entityStorage->getTableMapping($fields);

      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $fields */
      foreach ($fields as $fieldId => $fieldStorage) {
        if (!$fieldStorage instanceof BaseFieldDefinition) {
          continue;
        }

        if ($tableMapping->requiresDedicatedTableStorage($fieldStorage)) {
          $fieldTable = $tableMapping->getDedicatedDataTableName($fieldStorage);
          $fieldData = &$data[$fieldTable];

          // Remove handler keys within each field. Keys like 'title', 'help'
          // etc are ignored. Whereas 'argument', 'field', etc are removed.
          foreach ($fieldData as &$field) {
            $field = array_diff_key($field, $removeFieldKeys);
          }
        }
      }
    }
  }

  /**
   * Implements hook_field_views_data().
   *
   * @see \hook_field_views_data()
   * @see \date_recur_field_views_data()
   */
  public function fieldViewsData(FieldStorageConfigInterface $fieldDefinition): array {
    $data = [];

    $entityTypeId = $fieldDefinition->getTargetEntityTypeId();
    $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
    /** @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface $entityStorage */
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $tableMapping */
    $tableMapping = $entityStorage->getTableMapping();

    $fieldName = $fieldDefinition->getName();
    // The field label, see also usage in views.views.inc.
    $fieldLabel = $this->getFieldLabel($fieldDefinition->getTargetEntityTypeId(), $fieldDefinition->getName());
    $fieldTableName = $tableMapping->getDedicatedDataTableName($fieldDefinition);

    $parentData = $this->getParentFieldViewsData($fieldDefinition);
    if (empty($parentData)) {
      return $data;
    }
    $originalTable = $parentData[$fieldTableName];

    $occurrenceTableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($fieldDefinition);
    if ($this->database->schema()->tableExists($occurrenceTableName)) {
      $occurrenceTable = $originalTable;
      // Remove the automatic join, requires site builders to use relationship
      // plugin.
      unset($occurrenceTable['table']['join']);
      // Unset some irrelevant fields.
      foreach (array_keys($occurrenceTable) as $fieldId) {
        $fieldId = (string) $fieldId;
        if (($fieldId === 'table') || (strpos($fieldId, $fieldName . '_value', 0) !== FALSE) || (strpos($fieldId, $fieldName . '_end_value', 0) !== FALSE)) {
          continue;
        }
        unset($occurrenceTable[$fieldId]);
      }

      // Update table name references.
      $handlerTypes = $this->getViewsPluginTypes();
      $recurTableGroup = $this->t('Occurrences for @entity_type @field_name', [
        '@entity_type' => $entityType->getLabel(),
        '@field_name' => $fieldLabel,
      ]);
      foreach ($occurrenceTable as $fieldId => &$field) {
        $field['group'] = $recurTableGroup;

        foreach ($handlerTypes as $handlerType) {
          if (!empty($field[$handlerType]['table'])) {
            $field[$handlerType]['table'] = $occurrenceTableName;
            $field[$handlerType]['additional fields'] = [
              $fieldName . '_value',
              $fieldName . '_end_value',
              'delta',
              'field_delta',
            ];
          }
        }
      }

      $data[$occurrenceTableName] = $occurrenceTable;
    }

    $fieldTable = $originalTable;
    // Change the title for all plugins provided by
    // \datetime_range_field_views_data().
    foreach ($fieldTable as $key => &$definitions) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup|string|null $originalTitle */
      $originalTitle = $definitions['title'] ?? '';
      $tArgs = $originalTitle instanceof TranslatableMarkup ? $originalTitle->getArguments() : [];
      $tArgs['@field_label'] = $fieldLabel;

      if ($fieldName === $key) {
        $definitions['title'] = isset($tArgs['@argument']) ?
          $this->t('@field_label (@argument)', $tArgs) :
          $this->t('@field_label', $tArgs);
      }
      elseif (strpos($key, $fieldName . '_value', 0) !== FALSE) {
        $definitions['title'] = isset($tArgs['@argument']) ?
          $this->t('@field_label: first occurrence start date (@argument)', $tArgs) :
          $this->t('@field_label: first occurrence start date', $tArgs);
      }
      elseif (strpos($key, $fieldName . '_end_value', 0) !== FALSE) {
        $definitions['title'] = isset($tArgs['@argument']) ?
          $this->t('@field_label: first occurrence end date (@argument)', $tArgs) :
          $this->t('@field_label: first occurrence end date', $tArgs);
      }
      elseif (strpos($key, $fieldName . '_rrule', 0) !== FALSE) {
        $definitions['title'] = $this->t('@field_label: recurring rule', $tArgs);
      }
      elseif (strpos($key, $fieldName . '_timezone', 0) !== FALSE) {
        $definitions['title'] = $this->t('@field_label: time zone', $tArgs);
      }
      elseif (strpos($key, $fieldName . '_infinite', 0) !== FALSE) {
        $definitions['title'] = $this->t('@field_label: is infinite', $tArgs);
      }
      elseif ('delta' === $key) {
        $definitions['title'] = $this->t('@field_label: field delta', $tArgs);
      }
    }

    $data[$fieldTableName] = $fieldTable;
    return $data;
  }

  /**
   * Get date recur fields for entities supporting views.
   *
   * @return array
   *   An array of arrays of date recur fields keyed by entity type ID.
   */
  protected function getDateRecurFields(): array {
    // Date recur fields keyed by entity type id.
    $fields = [];

    // Get all date recur fields as base and attached fields.
    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      // \Drupal\views\EntityViewsData class only allows entities with
      // \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
      // Only fieldable entities have base fields.
      if (
        $this->entityTypeManager->getStorage($entityType->id()) instanceof SqlEntityStorageInterface &&
        $entityType->hasHandlerClass('views_data') &&
        $entityType->entityClassImplements(FieldableEntityInterface::class)) {
        $fields[$entityType->id()] = array_filter(
          $this->entityFieldManager->getFieldStorageDefinitions($entityType->id()),
          function (FieldStorageDefinitionInterface $field): bool {
            $typeDefinition = $this->typedDataManager->getDefinition('field_item:' . $field->getType());
            // @see \Drupal\date_recur\DateRecurCachedHooks::fieldInfoAlter
            return isset($typeDefinition[DateRecurOccurrences::IS_DATE_RECUR]);
          }
        );
      }
    }

    // Remove entity types with no date recur fields.
    $fields = array_filter($fields);

    return $fields;
  }

  /**
   * Get the most popular label for a field storage.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $fieldName
   *   The field.
   *
   * @return string
   *   The most popular label for a field storage.
   */
  protected function getFieldLabel($entityTypeId, $fieldName): string {
    return \views_entity_field_label($entityTypeId, $fieldName)[0];
  }

  /**
   * Get the views definition for the field, as defined by datetime_range.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $fieldDefinition
   *   A field storage definition.
   *
   * @return array
   *   The views data for a field.
   */
  protected function getParentFieldViewsData(FieldStorageConfigInterface $fieldDefinition): array {
    $this->moduleHandler->loadInclude('datetime_range', 'inc', 'datetime_range.views');
    return \datetime_range_field_views_data($fieldDefinition);
  }

  /**
   * Get an array of all views plugin types.
   *
   * @return array
   *   An array of all views plugin types.
   */
  protected function getViewsPluginTypes(): array {
    return Views::getPluginTypes();
  }

  /**
   * Get date format of field storage.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $fieldDefinition
   *   A field definition.
   *
   * @return string
   *   A date format.
   */
  protected function getFieldDateFormat(FieldStorageDefinitionInterface $fieldDefinition): string {
    return $fieldDefinition->getSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE
      ? DateTimeItemInterface::DATE_STORAGE_FORMAT
      : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
  }

}
