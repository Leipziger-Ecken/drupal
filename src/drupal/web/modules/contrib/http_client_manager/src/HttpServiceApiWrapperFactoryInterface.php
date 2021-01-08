<?php

namespace Drupal\http_client_manager;

use Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface;

/**
 * Interface HttpServiceApiWrapperFactoryInterface.
 *
 * @package Drupal\http_client_manager
 */
interface HttpServiceApiWrapperFactoryInterface {

  /**
   * Add an HTTP Service API wrapper.
   *
   * @param \Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface $wrapper
   *   A HTTP Service API Wrapper Service.
   * @param string $api
   *   The HTTP Service API name.
   */
  public function addApiWrapper(HttpServiceApiWrapperInterface $wrapper, $api);

  /**
   * Get HTTP Service API wrapper.
   *
   * @param string $name
   *   The HTTP Service API wrapper name.
   *
   * @return \Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface
   *   An HTTP Service API Wrapper Service.
   *
   * @throws \InvalidArgumentException
   *   Throws an InvalidArgumentException if the provided name does not exists.
   */
  public function get($name);

}
