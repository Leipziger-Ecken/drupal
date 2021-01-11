<?php

namespace Drupal\remove_http_headers\StackMiddleware;

use Drupal\remove_http_headers\Config\ConfigManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes removal of HTTP response headers.
 *
 * Runs after the page caching middleware took over the request.
 * Because it adds an additional HTTP header.
 */
class RemoveHttpHeadersMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The config manager service.
   *
   * @var \Drupal\remove_http_headers\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a RemoveHttpHeadersMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The decorated kernel.
   * @param \Drupal\remove_http_headers\Config\ConfigManager $configManager
   *   The config manager service.
   */
  public function __construct(HttpKernelInterface $httpKernel, ConfigManager $configManager) {
    $this->httpKernel = $httpKernel;
    $this->configManager = $configManager;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Only allow removal of HTTP headers on master request.
    if ($type === static::MASTER_REQUEST) {
      $response = $this->removeConfiguredHttpHeaders($response);
    }

    return $response;
  }

  /**
   * Remove configured HTTP headers.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The given response object
   *   without the HTTP response headers that should be removed.
   */
  protected function removeConfiguredHttpHeaders(Response $response): Response {
    $headersToRemove = $this->configManager->getHeadersToRemove();
    foreach ($headersToRemove as $httpHeaderToRemove) {
      $response->headers->remove($httpHeaderToRemove);
    }

    return $response;
  }

}
