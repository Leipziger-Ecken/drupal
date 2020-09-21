<?php

namespace Drupal\le_remote_api_services\EventSubscriber;

use Drupal\http_client_manager\Event\HttpClientEvents;
use Drupal\http_client_manager\Event\HttpClientHandlerStackEvent;
use GuzzleHttp\Middleware;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class HttpClientManagerDepotSocialSubscriber.
 */
class HttpClientManagerDepotSocialSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HttpClientEvents::HANDLER_STACK => ['onHandlerStack'],
    ];
  }

  /**
   * This method is called whenever the http_client.handler_stack event is
   * dispatched.
   *
   * @param \Drupal\http_client_manager\Event\HttpClientHandlerStackEvent $event
   *   The HTTP Client Handler stack event.
   */
  public function onHandlerStack(HttpClientHandlerStackEvent $event) {

    if ($event->getHttpServiceApi() != 'le_remote_api_services_depot_social.contents') {
      return;
    }

    $handler = $event->getHandlerStack();
    $middleware = Middleware::mapRequest([$this, 'addDepotSocialAuthCredentials']);
    $handler->push($middleware, 'le_remote_api_services_depot_social.contents');
  }

  /**
   * Add example service HTTP Header.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The current Request object.
   *
   * @return \Psr\Http\Message\MessageInterface
   *   Return an instance with the provided value for the specified header.
   */
  public function addDepotSocialAuthCredentials(RequestInterface $request) {
    $cookies = [
      'CHOCOLATECHIPSSL' => 'MWJhMTY4MjA1MDhmMjExZmI1ZjI4MzliMTQ5ZWIwMDQxNzI5OThiYWRhOWM5YTNjMzEwMzc2ZWQ2MjUzZjI0ZpWTqMgysnG3RKgjmchUb9ylqlRsCN',
      'SSESSe5620eae9b88d92599691c5b74ba6302' => 'dfozzFvG5tLJADJnkDLLsWEKyfe7OI6uJg_wOKmZf1M'
    ];

    $cookieJar = CookieJar::fromArray($cookies, 'leipzig.depot.social');

    return $request->withHeader('cookies', $cookies);
  }

}
