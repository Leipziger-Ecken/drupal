<?php

namespace Drupal\http_client_manager\Request;

/**
 * Class HttpRequestBase.
 *
 * @package Drupal\http_client_manager\Request
 */
abstract class HttpRequestBase implements HttpRequestInterface {

  /**
   * {@inheritdoc}
   */
  public function getArgs() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFallback() {
    return [];
  }

}
