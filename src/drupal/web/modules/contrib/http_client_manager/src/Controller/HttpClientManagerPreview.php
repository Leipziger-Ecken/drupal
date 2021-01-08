<?php

namespace Drupal\http_client_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\http_client_manager\HttpServiceApiHandler;

/**
 * Class HttpClientManagerPreview.
 *
 * @package Drupal\http_client_manager\Controller
 */
class HttpClientManagerPreview extends ControllerBase {

  /**
   * Drupal\http_client_manager\HttpServiceApiHandler definition.
   *
   * @var \Drupal\http_client_manager\HttpServiceApiHandler
   */
  protected $httpServicesApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpServiceApiHandler $http_services_api) {
    $this->httpServicesApi = $http_services_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client_manager.http_services_api')
    );
  }

  /**
   * View.
   *
   * @return string
   *   Return Hello string.
   */
  public function view() {
    $servicesApi = $this->httpServicesApi->getServicesApi();
    $header = [
      'id' => $this->t('ID'),
      'title' => $this->t('Title'),
      'base_uri' => $this->t('Base URI'),
      'operations' => $this->t('Operations'),
    ];
    $rows = [];
    foreach ($servicesApi as $api) {
      $rows[] = [
        'id' => $api['id'],
        'title' => $api['title'],
        'base_uri' => $api['config']['base_uri'],
        'operations' => ['data' => $this->buildOperations($api)],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no Http Services Api configured yet.'),
    ];
  }

  /**
   * Build operations.
   */
  protected function buildOperations($api) {
    return [
      '#type' => 'operations',
      '#links' => [
        'view' => [
          'title' => $this->t('View Commands'),
          'url' => Url::fromRoute('http_client_manager.http_service_api_preview_view', [
            'serviceApi' => $api['id'],
          ]),
        ],
      ],
    ];
  }

}
