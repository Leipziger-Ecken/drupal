<?php

namespace Drupal\http_client_manager\Event;

/**
 * Defines events for HTTP Client Manager clients.
 *
 * @package Drupal\http_client_manager\Event
 */
final class HttpClientEvents {

  /**
   * The name of the event fired when configuring client handlers stacks.
   *
   * This event allows you to add an handler to the stack whenever an HTTP
   * client is created. The event listener method receives a
   * \Drupal\http_client_manager\Event\HttpClientHandlerStackEvent instance.
   *
   * @Event
   */
  const HANDLER_STACK = 'http_client.handler_stack';

}
