<?php

namespace Drupal\http_client_manager\Event;

use GuzzleHttp\HandlerStack;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class HttpClientHandlerStackEvent.
 *
 * @package Drupal\http_client_manager\Event
 */
class HttpClientHandlerStackEvent extends Event {

  /**
   * The GuzzleHttp Handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $handlerStack;

  /**
   * The HTTP Service API id.
   *
   * @var string
   */
  protected $httpServiceApi;

  /**
   * HttpClientHandlerStackEvent constructor.
   *
   * @param \GuzzleHttp\HandlerStack $handler_stack
   *   The GuzzleHttp Handler stack.
   * @param string $http_service_api
   *   The HTTP Service Api id.
   */
  public function __construct(HandlerStack $handler_stack, $http_service_api) {
    $this->handlerStack = $handler_stack;
    $this->httpServiceApi = $http_service_api;
  }

  /**
   * Get Handler stack.
   *
   * @return \GuzzleHttp\HandlerStack
   *   The GuzzleHttp Handler stack.
   */
  public function getHandlerStack() {
    return $this->handlerStack;
  }

  /**
   * Get HTTP Service Api id.
   *
   * @return string
   *   The HTTP Service Api id.
   */
  public function getHttpServiceApi() {
    return $this->httpServiceApi;
  }

}
