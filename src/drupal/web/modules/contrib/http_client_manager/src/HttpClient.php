<?php

namespace Drupal\http_client_manager;

use Drupal\http_client_manager\Event\HttpClientEvents;
use Drupal\http_client_manager\Event\HttpClientHandlerStackEvent;
use Guzzle\Service\Loader\JsonLoader;
use Guzzle\Service\Loader\PhpLoader;
use Guzzle\Service\Loader\YamlLoader;
use GuzzleHttp\Client;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\HandlerStack;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The http client.
 */
class HttpClient implements HttpClientInterface {

  /**
   * The name of the service api of this http client instance.
   *
   * @var string
   */
  protected $serviceApi;

  /**
   * Description definition.
   *
   * @var \GuzzleHttp\Command\Guzzle\Description
   */
  protected $description;

  /**
   * The Http Service Api Handler service.
   *
   * @var HttpServiceApiHandler
   */
  protected $apiHandler;

  /**
   * An array containing the Http Service Api description.
   *
   * @var array
   */
  protected $api;

  /**
   * An array containing api source path info.
   *
   * @var array
   */
  protected $apiSourceInfo;

  /**
   * Guzzle Client definition.
   *
   * @var \GuzzleHttp\Command\Guzzle\GuzzleClient
   */
  protected $client;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * An array containing all the Guzzle commands.
   *
   * @var array
   */
  protected $commands;

  /**
   * The file locator used to find the service descriptions.
   *
   * @var \Symfony\Component\Config\FileLocator
   */
  protected $fileLocator;

  /**
   * The file loader used to load the service descriptions.
   *
   * @var \Guzzle\Service\Loader\FileLoader
   */
  protected $fileLoader;

  /**
   * Constructs an HttpClient object.
   *
   * @param string $serviceApi
   *   The service api name for this instance.
   * @param \Drupal\http_client_manager\HttpServiceApiHandlerInterface $apiHandler
   *   The service api handler instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher instance.
   */
  public function __construct($serviceApi, HttpServiceApiHandlerInterface $apiHandler, EventDispatcherInterface $event_dispatcher) {
    $this->serviceApi = $serviceApi;
    $this->apiHandler = $apiHandler;
    $this->api = $this->apiHandler->load($this->serviceApi);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getApi() {
    return $this->api;
  }

  /**
   * Get Api source path info.
   *
   * @return array
   *   An array containing api source path info.
   */
  protected function getApiSourceInfo() {
    if (empty($this->apiSourceInfo)) {
      $this->setApiSourceInfo();
    }
    return $this->apiSourceInfo;
  }

  /**
   * Set Api source path info.
   */
  protected function setApiSourceInfo() {
    $this->apiSourceInfo = pathinfo($this->api['source']);
  }

  /**
   * Get Client.
   *
   * @return \GuzzleHttp\Command\Guzzle\GuzzleClient
   *   The Configured Guzzle client instance.
   */
  protected function getClient() {
    if (empty($this->client)) {
      $this->setupGuzzleClient();
    }
    return $this->client;
  }

  /**
   * Get Client by Command.
   *
   * Get or create an instance of the Guzzle Client, with overridden
   * configurations or not, based on the given command name.
   * If the service api description has been overridden via settings.php and
   * has been defined a "commands['whitelist']" or "commands['blacklist']"
   * property, the following algorithm will be applied:
   * - Commands in blacklist or not in whitelist must not use service overrides.
   * - All the other commands will use the service overrides.
   *
   * @param string $commandName
   *   The Guzzle Command name.
   *
   * @return \GuzzleHttp\Command\Guzzle\GuzzleClient
   *   The Configured Guzzle client instance
   */
  protected function getClientByCommand($commandName) {
    $api = $this->getApi();
    if (empty($api['orig']) || empty($api['commands'])) {
      return $this->getClient();
    }
    $cmds = $api['commands'];
    $config = $api['config'];

    // Commands in blacklist or not in whitelist must not use service overrides.
    if (
      (!empty($cmds['blacklist']) && in_array($commandName, $cmds['blacklist'])) ||
      (!empty($cmds['whitelist']) && !in_array($commandName, $cmds['whitelist']))
    ) {
      $config = $api['orig']['config'];
      $config['handler'] = $this->getClientConfig()['handler'];
    }

    $this->client = $this->createGuzzleClient($config);
    return $this->client;
  }

  /**
   * Setup Guzzle Client from *.http_services_api.yml files.
   */
  private function setupGuzzleClient() {
    $config = $this->getClientConfig();
    $this->client = $this->createGuzzleClient($config);
  }

  /**
   * Create a new Guzzle Client by config.
   *
   * @param array $config
   *   An array of configurations used to create a new Guzzle Client.
   *
   * @return \GuzzleHttp\Command\Guzzle\GuzzleClient
   *   A Guzzle Client instance.
   */
  private function createGuzzleClient(array $config) {
    $client = new Client($config);
    return new GuzzleClient($client, $this->loadServiceDescription($config));
  }

  /**
   * {@inheritdoc}
   */
  public function getClientConfig() {
    $api = $this->getApi();
    $config = !empty($api['config']) ? $api['config'] : [];
    $config['handler'] = HandlerStack::create();

    if (!empty($config['debug']) && is_file($config['debug'])) {
      $config['debug'] = fopen($config['debug'], 'a');
    }

    $event = new HttpClientHandlerStackEvent($config['handler'], $this->serviceApi);
    $this->eventDispatcher->dispatch(HttpClientEvents::HANDLER_STACK, $event);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadServiceDescription($config) {
    $base_uri = $config['base_uri'];
    if (empty($this->description[$base_uri])) {
      $source = $this->getApiSourceInfo();
      $loader = $this->getFileLoader();
      $locator = $this->getFileLocator();

      $description = $loader->load($locator->locate($source['basename']));
      $description['baseUrl'] = $base_uri;
      $this->description[$base_uri] = new Description($description);
    }
    return $this->description[$base_uri];
  }

  /**
   * Get File Locator.
   *
   * @return \Symfony\Component\Config\FileLocator
   *   The file locator used to find the service descriptions.
   */
  protected function getFileLocator() {
    if (empty($this->fileLocator)) {
      $this->initFileLocator();
    }
    return $this->fileLocator;
  }

  /**
   * Set File Locator.
   */
  protected function initFileLocator() {
    $source = $this->getApiSourceInfo();
    $this->fileLocator = new FileLocator($source['dirname']);
  }

  /**
   * Get File Loader.
   *
   * @return \Guzzle\Service\Loader\FileLoader
   *   The file loader used to load the service descriptions.
   */
  protected function getFileLoader() {
    if (empty($this->fileLoader)) {
      $this->initFileLoader();
    }
    return $this->fileLoader;
  }

  /**
   * Set File Loader.
   */
  protected function initFileLoader() {
    $source = $this->getApiSourceInfo();
    $locator = $this->getFileLocator();

    switch ($source['extension']) {
      case 'json':
        $loader = new JsonLoader($locator);
        break;

      case 'yml':
        $loader = new YamlLoader($locator);
        break;

      case 'php':
        $loader = new PhpLoader($locator);
        break;

      default:
        $allowed_extensions = ['json', 'yml', 'php'];
        $message = sprintf('Invalid HTTP Services Api source provided: "%s". ', $source['filename']);
        $message .= sprintf('File extension must be one of %s.', implode(', ', $allowed_extensions));
        throw new \RuntimeException($message);
    }

    $this->fileLoader = $loader;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    if (!empty($this->commands)) {
      return $this->commands;
    }

    $description = $this->getClient()->getDescription();
    $command_names = array_keys($description->getOperations());
    $this->commands = [];

    foreach ($command_names as $command_name) {
      $this->commands[$command_name] = $description->getOperation($command_name);
    }
    return $this->commands;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommand($commandName) {
    if (!empty($this->commands[$commandName])) {
      return $this->commands[$commandName];
    }
    return $this->getClient()->getDescription()->getOperation($commandName);
  }

  /**
   * {@inheritdoc}
   */
  public function call($commandName, array $params = []) {
    $client = $this->getClientByCommand($commandName);
    $command = $client->getCommand($commandName, $params);
    return $client->execute($command);
  }

  /**
   * Magic method implementation for commands execution.
   *
   * @param string $name
   *   The Guzzle command name.
   * @param array $arguments
   *   The Guzzle command parameters array.
   *
   * @return \GuzzleHttp\Command\ResultInterface|mixed
   *   The Guzzle Command execution result.
   *
   * @see HttpClientInterface::call
   */
  public function __call($name, array $arguments = []) {
    $params = !empty($arguments[0]) ? $arguments[0] : [];
    return $this->call($name, $params);
  }

}
