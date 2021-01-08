<?php

namespace Drupal\http_client_manager\Plugin\HttpServiceApiWrapper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\http_client_manager\Entity\HttpConfigRequest;
use Drupal\http_client_manager\Entity\HttpConfigRequestInterface;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Drupal\http_client_manager\Request\HttpRequestInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;

/**
 * Class HttpServiceApiWrapperBase.
 *
 * @package Drupal\http_client_manager\Plugin\HttpServiceWrappers
 */
abstract class HttpServiceApiWrapperBase implements HttpServiceApiWrapperInterface {

  use StringTranslationTrait;

  /**
   * The cache id prefix.
   */
  const CACHE_ID_PREFIX = 'http_config_request';

  /**
   * The Http Client Factory Service.
   *
   * @var \Drupal\http_client_manager\HttpClientManagerFactoryInterface
   */
  protected $httpClientFactory;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Language Manager Service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * HttpServiceApiWrapperBase constructor.
   *
   * @param \Drupal\http_client_manager\HttpClientManagerFactoryInterface $http_client_factory
   *   The Http Client Factory Service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Http Client Manager cache bin.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Language Manager Service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger Service.
   */
  public function __construct(HttpClientManagerFactoryInterface $http_client_factory, CacheBackendInterface $cache, AccountProxyInterface $current_user, LanguageManagerInterface $language_manager, MessengerInterface $messenger) {
    $this->httpClientFactory = $http_client_factory;
    $this->cache = $cache;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
  }

  /**
   * Call REST web services.
   *
   * @param string $command
   *   The command name.
   * @param array $args
   *   The command arguments.
   * @param mixed $fallback
   *   The fallback value in case of exception.
   *
   * @return \GuzzleHttp\Command\ResultInterface
   *   The service result.
   */
  protected function call($command, array $args = [], $fallback = []) {
    $httpClient = $this->gethttpClient();
    $http_method = $httpClient->getCommand($command)->getHttpMethod();

    try {
      return $httpClient->call($command, $args);
    }
    catch (CommandException $e) {
      $this->logError($e);

      if (strtolower($http_method) != 'get') {
        $fallback = [
          'error' => TRUE,
          'message' => $e->getMessage(),
        ];
      }
      return new Result($fallback);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function httpConfigRequest($request_name, $expire = FALSE, array $tags = []) {
    $request = HttpConfigRequest::load($request_name);
    if (empty($request)) {
      $args = ['%name' => $request_name];
      $message = $this->t('Undefined HTTP Config Request "%name"', $args);
      throw new \InvalidArgumentException($message);
    }

    if ($expire !== FALSE) {
      return $this->getCachedHttpConfigRequest($request, $expire, $tags);
    }

    try {
      $data = $request->execute()->toArray();
    }
    catch (CommandException $e) {
      $this->logError($e);
      $data = [];
    }
    return $data;
  }

  /**
   * Get cached HTTP Config Request.
   *
   * @param \Drupal\http_client_manager\Entity\HttpConfigRequestInterface $request
   *   The HTTP Config Request to be executed.
   * @param int $expire
   *   The cache expiry time.
   * @param array $tags
   *   An array of cache tags.
   *
   * @return array
   *   The Response array.
   */
  protected function getCachedHttpConfigRequest(HttpConfigRequestInterface $request, $expire, array $tags = []) {
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    $cid = self::CACHE_ID_PREFIX . ':' . $request->id() . ':' . $lang;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    try {
      $data = $request->execute()->toArray();
      $this->cache->set($cid, $data, $expire, $tags);
    }
    catch (CommandException $e) {
      $this->logError($e);
      $data = [];
    }
    return $data;
  }

  /**
   * Logs a command exception.
   *
   * This method is meant to be overridden by any Service Api Wrapper.
   * By default it prints the error message by using the Messenger service.
   *
   * @param \GuzzleHttp\Command\Exception\CommandException $e
   *   The Command Exception object.
   */
  protected function logError(CommandException $e) {
    $this->messenger->addError($e->getMessage());
  }

  /**
   * Get response.
   *
   * This method is meant to be overridden by any Service Api Wrapper.
   * By default it returns the result array, but it can be used to check if the
   * given response contains errors.
   *
   * @param \GuzzleHttp\Command\ResultInterface $result
   *   The command response.
   *
   * @return array
   *   The response array.
   */
  protected function getResponse(ResultInterface $result) {
    return $result->toArray();
  }

  /**
   * Call by Request.
   *
   * @param \Drupal\http_client_manager\Request\HttpRequestInterface $request
   *   The Request bean.
   *
   * @return array|bool
   *   The service response.
   */
  protected function callByRequest(HttpRequestInterface $request) {
    $result = $this->call($request->getCommand(), $request->getArgs(), $request->getFallback());
    return $this->getResponse($result);
  }

}
