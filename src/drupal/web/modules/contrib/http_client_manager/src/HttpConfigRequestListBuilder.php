<?php

namespace Drupal\http_client_manager;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a listing of Http Config Request entities.
 */
class HttpConfigRequestListBuilder extends ConfigEntityListBuilder {

  /**
   * Current Request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RequestStack $request_stack) {
    parent::__construct($entity_type, $storage);
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('request_stack')
    );
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('service_api', $this->request->get('serviceApi'))
      ->condition('command_name', $this->request->get('commandName'))
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Http Config Request');
    $header['id'] = $this->t('Machine name');
    $header['params'] = $this->t('Parameters');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $params = [];

    foreach ($entity->getParameters() as $key => $value) {
      $params[$key] = $this->t('@key: @value', [
        '@key' => $key,
        '@value' => is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value,
      ]);
    }
    $row['params']['data'] = [
      '#theme' => 'item_list',
      '#items' => $params,
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('execute') && $entity->hasLinkTemplate('execute')) {
      $operations['execute'] = [
        'title' => $this->t('Execute'),
        'weight' => 101,
        'url' => Url::fromRoute('entity.http_config_request.execute', [
          'http_config_request' => $entity->id(),
          'serviceApi' => $entity->get('service_api'),
          'commandName' => $entity->get('command_name'),
        ]),
      ];
    }

    return $operations;
  }

}
