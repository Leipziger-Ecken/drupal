<?php

namespace Drupal\http_client_manager\Plugin\HttpServiceApiWrapper;

/**
 * Interface HttpServiceApiWrapperInterface.
 *
 * @package Drupal\http_client_manager\Plugin\HttpServiceWrappers
 */
interface HttpServiceApiWrapperInterface {

  /**
   * Get HTTP Client.
   *
   * @return \Drupal\http_client_manager\HttpClientInterface
   *   The HTTP Client used to make requests.
   */
  public function getHttpClient();

  /**
   * Executes an HTTP Config Request.
   *
   * @param string $request_name
   *   The Http Config Request name.
   * @param int|bool $expire
   *   The expire time, Cache::PERMANENT or FALSE if cache has not to be used.
   * @param array $tags
   *   An array of cache tags to be used if $expire !== FALSE.
   *
   * @return array
   *   The Response array.
   */
  public function httpConfigRequest($request_name, $expire = FALSE, array $tags = []);

}
