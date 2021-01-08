<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GoogleMapsService.
 */
class GoogleMapsService {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Gmap Api Key.
   *
   * @var string
   */
  protected $gmapApiKey;

  /**
   * The Gmap Api Key.
   *
   * @var array
   */
  public $gmapApiLocalization = [
    'default' => 'maps.googleapis.com/maps/api/js',
    'china' => 'maps.google.cn/maps/api/js',
  ];

  /**
   * Set the module related Gmap API Key.
   *
   * @return string
   *   The GmapApiKey
   */
  protected function setGmapApiKey() {
    return $this->config->get('geofield_map.settings')->get('gmap_api_key');
  }

  /**
   * Constructs a new GoogleMapsService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    RequestStack $request_stack
  ) {
    $this->config = $config_factory;
    $this->languageManager = $language_manager;
    $this->gmapApiKey = $this->setGmapApiKey();
    $this->requestStack = $request_stack;
  }

  /**
   * Get the module related Gmap API Key.
   *
   * @return string
   *   The GmapApiKey
   */
  public function getGmapApiKey() {
    return $this->gmapApiKey;
  }

  /**
   * Get the localized Gmap API Library.
   *
   * @param string $index
   *   The index parameter.
   *
   * @return string
   *   The Gmap Api library base Url
   */
  public function getGmapApiLocalization($index = 'default') {

    // In case of China, the google maps api should be called as not secure,
    // and this is possible only for not ssl web requests.
    $web_protocol = 'https://';
    if ($index == 'china' && !$this->requestStack->getCurrentRequest()->isSecure()) {
      $web_protocol = 'http://';
    }
    return isset($this->gmapApiLocalization[$index]) ? $web_protocol . $this->gmapApiLocalization[$index] : $web_protocol . $this->gmapApiLocalization['default'];
  }

}
