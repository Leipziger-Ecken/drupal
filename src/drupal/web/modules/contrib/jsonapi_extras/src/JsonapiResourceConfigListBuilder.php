<?php

namespace Drupal\jsonapi_extras;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository;
use Drupal\jsonapi_extras\ResourceType\NullJsonapiResourceConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of JSON:API Resource Config entities.
 */
class JsonapiResourceConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * The JSON:API configurable resource type repository.
   *
   * @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The JSON:API extras config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs new JsonapiResourceConfigListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage.
   * @param \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceTypeRepository $resource_type_repository
   *   The JSON:API configurable resource type repository.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config instance.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ConfigurableResourceTypeRepository $resource_type_repository, ImmutableConfig $config) {
    parent::__construct($entity_type, $storage);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('config.factory')->get('jsonapi_extras.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'name' => $this->t('Name'),
      'path' => $this->t('Path'),
      'state' => $this->t('State'),
      'operations' => $this->t('Operations'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $list = [];
    $resource_status = [
      'enabled' => t('Enabled Resources'),
      'disabled' => t('Disabled resources'),
    ];

    $title = $this->t('Filter resources by name, entity type, bundle or path.');
    $list['status']['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#placeholder' => $title,
      '#attributes' => [
        'class' => ['jsonapi-resources-filter-text'],
        'data-table' => '.jsonapi-resources-table',
        'autocomplete' => 'off',
        'title' => $title,
      ],
    ];

    foreach ($resource_status as $status => $label) {
      $list[$status] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => $status === 'enabled',
        '#attributes' => [
          'id' => 'jsonapi-' . $status . '-resources-list',
        ],
        '#attached' => [
          'library' => [
            'jsonapi_extras/admin',
          ],
        ],
      ];

      $list[$status]['table'] = [
        '#type' => 'table',
        '#header' => [
          'name' => $this->t('Name'),
          'path' => $this->t('Path'),
          'state' => $this->t('State'),
          'operations' => $this->t('Operations'),
        ],
        '#attributes' => [
          'class' => [
            'jsonapi-resources-table',
          ],
        ],
        '#attached' => [
          'library' => [
            'jsonapi_extras/admin',
          ],
        ],
      ];
    }

    $prefix = $this->config->get('path_prefix');
    /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType[] $resource_types */
    $resource_types = $this->resourceTypeRepository->all();
    foreach ($resource_types as $resource_type) {
      /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource_config */
      $resource_config = $resource_type->getJsonapiResourceConfig();

      if ($resource_type->isInternal() && !$resource_config->get('disabled')) {
        continue;
      }

      /** @var \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type */
      $entity_type_id = $resource_type->getEntityTypeId();
      $bundle = $resource_type->getBundle();

      $row = [
        'name' => ['#plain_text' => $resource_type->getTypeName()],
        'path' => [
          '#type' => 'html_tag',
          '#tag' => 'code',
          '#value' => sprintf('/%s%s', $prefix, $resource_type->getPath()),
        ],
        'state' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t('Default'),
          '#attributes' => [
            'class' => [
              'label',
            ],
          ],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'overwrite' => [
              'title' => t('Overwrite'),
              'weight' => -10,
              'url' => Url::fromRoute('entity.jsonapi_resource_config.add_form', [
                'entity_type_id' => $entity_type_id,
                'bundle' => $bundle,
              ]),
            ],
          ],
        ],
      ];

      if (!$resource_config instanceof NullJsonapiResourceConfig) {
        $row['state']['#value'] = $this->t('Overwritten');
        $row['state']['#attributes']['class'][] = 'label--overwritten';
        $row['operations']['#links'] = $this->getDefaultOperations($resource_config);
        $row['operations']['#links']['delete']['title'] = $this->t('Revert');
      }

      $list[$resource_config->get('disabled') ? 'disabled' : 'enabled']['table'][] = $row;
    }

    return $list;
  }

}
