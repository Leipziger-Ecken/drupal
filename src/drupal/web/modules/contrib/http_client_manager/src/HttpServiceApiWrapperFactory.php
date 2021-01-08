<?php

namespace Drupal\http_client_manager;

use Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface;

/**
 * Class HttpServiceApiWrapperFactory.
 *
 * @package Drupal\http_client_manager
 */
class HttpServiceApiWrapperFactory implements HttpServiceApiWrapperFactoryInterface {

  /**
   * An array of HTTP Service Api Wrapper Services.
   *
   * @var \Drupal\http_client_manager\Plugin\HttpServiceApiWrapper\HttpServiceApiWrapperInterface[]
   */
  protected $apiWrappers;

  /**
   * HttpServiceApiWrapperFactory constructor.
   */
  public function __construct() {
    $this->apiWrappers = [];
  }

  /**
   * {@inheritdoc}
   */
  public function addApiWrapper(HttpServiceApiWrapperInterface $wrapper, $api) {
    $this->apiWrappers[$api] = $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    if (!isset($this->apiWrappers[$name])) {
      $message = sprintf('Cannot find an api wrapper with the name "%s', $name);
      throw new \InvalidArgumentException($message);
    }
    return $this->apiWrappers[$name];
  }

}
