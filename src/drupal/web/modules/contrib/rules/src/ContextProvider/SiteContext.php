<?php

namespace Drupal\rules\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Sets the current node as a context on node routes.
 *
 * Modules may add properties to this global context by implementing
 * hook_data_type_info_alter(&$data_types) to modify the $data_types['site']
 * element.
 *
 * @todo Need a way to alter the global context contents to set a value for
 * any added site properties.
 */
class SiteContext implements ContextProviderInterface {
  use StringTranslationTrait;

  /**
   * The system.site configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemSiteConfig;

  /**
   * Constructs a new SiteContext.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->systemSiteConfig = $config_factory->get('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $site = [
      'url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      'login-url' => Url::fromRoute('user.page', [], ['absolute' => TRUE])->toString(),
      'name' => $this->systemSiteConfig->get('name'),
      'slogan' => $this->systemSiteConfig->get('slogan'),
      'mail' => $this->systemSiteConfig->get('mail'),
    ];

    $context_definition = new ContextDefinition('site', $this->t('Site information'));
    $context = new Context($context_definition, $site);
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['site']);
    $context->addCacheableDependency($cacheability);

    $result = [
      'site' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
