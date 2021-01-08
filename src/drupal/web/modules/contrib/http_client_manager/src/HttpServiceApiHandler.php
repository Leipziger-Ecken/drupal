<?php

namespace Drupal\http_client_manager;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Controller\ControllerResolver;

/**
 * Class HttpServiceApiHandler.
 *
 * @package Drupal\http_client_manager
 */
class HttpServiceApiHandler implements HttpServiceApiHandlerInterface {

  /**
   * Defines the required property value.
   */
  const REQUIRED_PROPERTY = TRUE;

  /**
   * Drupal root.
   *
   * @var string
   */
  protected $root;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\StringTranslation\TranslationManager definition.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * Drupal\Core\Controller\ControllerResolver definition.
   *
   * @var \Drupal\Core\Controller\ControllerResolver
   */
  protected $controllerResolver;

  /**
   * All defined services api descriptions.
   *
   * @var array
   */
  protected $servicesApi;

  /**
   * The HTTP Client Manager config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * HttpServiceApiHandler constructor.
   *
   * @param string $root
   *   The Application root.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation manager.
   * @param \Drupal\Core\Controller\ControllerResolver $controller_resolver
   *   The controller resolver service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct($root, ModuleHandler $module_handler, TranslationManager $string_translation, ControllerResolver $controller_resolver, ConfigFactoryInterface $config_factory) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    $this->controllerResolver = $controller_resolver;
    $this->config = $config_factory->get('http_client_manager.settings');
    $this->servicesApi = $this->getServicesApi();
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('http_services_api', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getServicesApi() {
    if (empty($this->servicesApi)) {
      $this->buildServicesApiYaml();
    }
    return $this->servicesApi;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if (empty($this->servicesApi[$id])) {
      $message = sprintf('Undefined Http Service Api id "%s"', $id);
      throw new \InvalidArgumentException($message);
    }
    return $this->servicesApi[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function moduleProvidesApi($module_name) {
    $servicesApi = $this->getServicesApi();
    foreach ($servicesApi as $serviceApi) {
      if ($serviceApi['provider'] == $module_name) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Builds all services api provided by .http_services_api.yml files.
   *
   * Each service api is an array with the following keys:
   *   - id: The machine name of the Service Api.
   *   - title: The human-readable name of the API.
   *   - api_path: The Guzzle description path (relative to module directory).
   *   - provider: The provider module of the Service Api.
   *   - source: The absolute path to the Service API description file.
   *   - config: An array of additional configurations for the HttpClient class.
   *
   * @code
   * example_service:
   *   title: "Example Service"
   *   api_path: src/HttpService/example_service.json
   *   config:
   *     base_uri: "http://www.example.com/api/v1"
   *     timeout: 4
   *     connect_timeout: 3
   *     auth: ['username', 'secretPassword', 'Basic']
   * @endcode
   */
  protected function buildServicesApiYaml() {
    $this->servicesApi = [];
    $items = $this->getYamlDiscovery()->findAll();

    foreach ($items as $provider => $servicesApi) {
      $module_path = $this->moduleHandler->getModule($provider)->getPath();

      foreach ($servicesApi as $id => $serviceApi) {
        $this->overrideServiceApiDefinition($id, $serviceApi);
        $this->validateServiceApiDefinition($id, $serviceApi);
        $default = [
          'id' => $id,
          'provider' => $provider,
          'source' => $this->root . '/' . $module_path . '/' . $serviceApi['api_path'],
          'config' => [],
        ];
        $serviceApi = array_merge($default, $serviceApi);

        if (!empty($serviceApi[$id]['orig'])) {
          $serviceApi[$id]['orig'] = array_merge($default, $serviceApi[$id]['orig']);
        }

        $this->servicesApi[$id] = $serviceApi;
      }
    }
  }

  /**
   * Override Service API definition.
   *
   * Checks for overriding configurations in settings.php for the given Service
   * API Definition.
   *
   * @param string $id
   *   The service api id.
   * @param array $serviceApi
   *   An array of service api definition.
   */
  protected function overrideServiceApiDefinition($id, array &$serviceApi) {
    $settings = Settings::get('http_services_api', []);
    if (empty($settings[$id]) || !$this->config->get('enable_overriding_service_definitions')) {
      return;
    }

    $original = $serviceApi;
    $overrides = self::getOverridableProperties();
    $settings[$id] = array_intersect_key($settings[$id], $overrides);
    $serviceApi = array_replace_recursive($serviceApi, $settings[$id]);

    // Add the "orig" key only if the commands override has been specified.
    if (!empty($serviceApi['commands'])) {
      $serviceApi['orig'] = $original;
    }
  }

  /**
   * Get overridable Service API properties.
   *
   * @return array
   *   An associative array where keys are overridable property names and values
   *   are boolean indicating if the property is required or not.
   */
  public static function getOverridableProperties() {
    return [
      'title' => self::REQUIRED_PROPERTY,
      'api_path' => self::REQUIRED_PROPERTY,
      'config' => self::REQUIRED_PROPERTY,
      'commands' => !self::REQUIRED_PROPERTY,
    ];
  }

  /**
   * Validates Service api definition.
   *
   * @param string $id
   *   The service api id.
   * @param array $serviceApi
   *   An array of service api definition.
   *
   * @throws \RuntimeException
   *   In case of invalid HTTP Service API definition.
   */
  protected function validateServiceApiDefinition($id, array $serviceApi) {
    foreach (self::getOverridableProperties() as $property => $isRequired) {
      if ($isRequired && !isset($serviceApi[$property])) {
        $message = sprintf('Missing required parameter "%s" in "%s" service api definition', $property, $id);
        throw new \RuntimeException($message);
      }
    }

    if (!empty($serviceApi['commands']['blacklist']) && !empty($serviceApi['commands']['whitelist'])) {
      $message = sprintf('You cannot specify both "blacklist" and "whitelist" parameters in "%s" service api definition', $id);
      throw new \RuntimeException($message);
    }
  }

  /**
   * Returns all module names.
   *
   * @return string[]
   *   Returns the human readable names of all modules keyed by machine name.
   */
  protected function getModuleNames() {
    $modules = [];
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $modules[$module] = $this->moduleHandler->getName($module);
    }
    asort($modules);
    return $modules;
  }

}
