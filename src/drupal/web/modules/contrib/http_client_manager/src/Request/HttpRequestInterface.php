<?php

namespace Drupal\http_client_manager\Request;

/**
 * Interface HttpRequestInterface.
 *
 * @package Drupal\http_client_manager\Request
 */
interface HttpRequestInterface {

  /**
   * Returns the GuzzleHttp Service command name.
   *
   * @return string
   *   The service command name.
   */
  public function getCommand();

  /**
   * Returns an array of arguments.
   *
   * @return array
   *   Array of argument.
   */
  public function getArgs();

  /**
   * The fallback result.
   *
   * @return mixed
   *   The fallback result.
   */
  public function getFallback();

}
