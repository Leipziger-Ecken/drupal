<?php

namespace Drupal\rules\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of RulesComponentConfig entities.
 *
 * @see \Drupal\rules\Entity\RulesComponent
 */
class RulesComponentListBuilder extends ConfigEntityListBuilder {

  /**
   * The expression manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManagerInterface
   */
  protected $expressionManager;

  /**
   * Constructs a new RulesComponentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\rules\Engine\ExpressionManagerInterface $expression_manager
   *   The rules expression plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ExpressionManagerInterface $expression_manager) {
    parent::__construct($entity_type, $storage);
    // Disable the pager because this list builder uses client-side filters,
    // which requires all entities to be listed.
    $this->limit = FALSE;
    $this->expressionManager = $expression_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.rules_expression')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['label'] = $this->t('Rules Component');
    $header['plugin'] = $this->t('Type');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $expression_config = $entity->getExpression()->getConfiguration();
    $definition = $this->expressionManager->getDefinition($expression_config['id']);
    $plugin_type = $definition['label'];

    /** @var \Drupal\rules\Entity\RulesComponentConfig $entity */
    $details = $this->t('Machine name: @name', ['@name' => $entity->id()]);
    if ($entity->hasTags()) {
      $details = $details . '<br />' . $this->t('Tags: @tags', ['@tags' => implode(', ', $entity->getTags())]);
    }

    $row['label']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['label']['data'] = [
      '#plain_text' => $entity->label(),
      '#suffix' => '<div class="description">' . $details . '</div>',
    ];
    $row['plugin']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['plugin']['data'] = [
      '#plain_text' => $plugin_type,
    ];
    $row['description']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['description']['data'] = [
      '#type' => 'processed_text',
      '#text' => $entity->getDescription(),
      '#format' => 'restricted_html',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'rules-entity-list';
    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['#attached']['library'][] = 'rules/rules_ui.listing';

    $build['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Components are stand-alone sets of Rules configuration that can be used by Rules and other modules on your site. Components are for example useful if you want to use the same conditions, actions or rules in multiple places, or call them from your custom module. You may also export each component separately. See the <a href=":documentation">online documentation</a> for more information about how to use components.', [':documentation' => 'https://www.drupal.org/node/1300024']),
      '#suffix' => '</p>',
    ];

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $build['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#placeholder' => $this->t('Filter by component name, machine name, description, or tag'),
      '#attributes' => [
        'class' => ['rules-filter-text'],
        'data-table' => '.rules-listing-table',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the component name, machine name, description, or tag to filter by.'),
      ],
    ];

    $build += parent::render();
    $build['table']['#empty'] = $this->t('No rules components have been defined.');
    $build['table']['#attributes'] = ['class' => ['rules-listing-table']];

    return $build;
  }

}
