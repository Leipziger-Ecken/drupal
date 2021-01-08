<?php

namespace Drupal\http_client_manager;

/**
 * Interface HttpClientManagerFactoryInterface.
 *
 * @package Drupal\http_client_manager
 */
interface HttpClientManagerFactoryInterface {

  /**
   * Retrieves the registered http client for the requested service api.
   *
   * @param string $service_api
   *   The service api name.
   *
   * @return \Drupal\http_client_manager\HttpClientInterface
   *   The registered http client for this service api.
   */
  public function get($service_api);

}
