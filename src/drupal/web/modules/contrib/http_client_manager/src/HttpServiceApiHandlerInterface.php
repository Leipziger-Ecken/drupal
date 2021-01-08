<?php

namespace Drupal\http_client_manager;

/**
 * Interface HttpServiceApiHandlerInterface.
 *
 * @package Drupal\http_client_manager
 */
interface HttpServiceApiHandlerInterface {

  /**
   * Gets all available services Api.
   *
   * @code
   * example_api:
   *   title: "Example fake API"
   *   api_path: "src/api/example-api.json"
   *   base_url: "http://www.example.com/services"
   *
   * another_example_api:
   *   title: "Another Example fake API"
   *   api_path: "../../../vendor/example/api/another-example-api.json"
   *   base_url: "http://api.example.com/v2"
   * @endcode
   *
   * @return array
   *   An array whose keys are api names and whose corresponding values
   *   are arrays containing the following key-value pairs:
   *   - title: The human-readable name of the API.
   *   - api_path: The Guzzle description path (relative to module directory).
   *   - base_url: The Service API base url.
   */
  public function getServicesApi();

  /**
   * Load Service Api description.
   *
   * @param string $id
   *   The Service Api id.
   *
   * @return mixed|null
   *   The Service description array or null.
   */
  public function load($id);

  /**
   * Determines whether a module provides some service API.
   *
   * @param string $module_name
   *   The module name.
   *
   * @return bool
   *   Returns TRUE if the module provides some service API, otherwise FALSE.
   */
  public function moduleProvidesApi($module_name);

}
