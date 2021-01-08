<?php

namespace Drupal\http_client_manager;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class HttpClientManagerFactory.
 *
 * @package Drupal\http_client_manager
 */
class HttpClientManagerFactory implements HttpClientManagerFactoryInterface {

  use ContainerAwareTrait;

  /**
   * An array of HTTP Clients.
   *
   * @var array
   */
  protected $clients = [];

  /**
   * {@inheritdoc}
   */
  public function get($service_api) {
    if (!isset($this->clients[$service_api])) {
      $apiHandler = $this->container->get('http_client_manager.http_services_api');
      $eventDispatcher = $this->container->get('event_dispatcher');
      $this->clients[$service_api] = new HttpClient($service_api, $apiHandler, $eventDispatcher);
    }
    return $this->clients[$service_api];
  }

}
