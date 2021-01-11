<?php

namespace Drupal\rules\Plugin\Condition;

use Drupal\ban\BanIpManagerInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an 'IP address is blocked' condition.
 *
 * @Condition(
 *   id = "rules_ip_is_banned",
 *   label = @Translation("IP address is banned"),
 *   category = @Translation("Ban"),
 *   provider = "ban",
 *   context_definitions = {
 *     "ip" = @ContextDefinition("string",
 *       label = @Translation("IP Address"),
 *       description = @Translation("Determine if an IP address is banned using the Ban Module. If no IP is provided, the current user IP is used."),
 *       default_value = NULL,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class IpIsBanned extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The ban manager used to check the IP.
   *
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $banManager;

  /**
   * The corresponding request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ban.ip_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Constructs a IpIsBanned object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ban\BanIpManagerInterface $ban_manager
   *   The ban manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The corresponding request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BanIpManagerInterface $ban_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->banManager = $ban_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Checks if an IP address is banned.
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP address is banned.
   */
  protected function doEvaluate($ip = NULL) {
    if (!isset($ip)) {
      $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    return $this->banManager->isBanned($ip);
  }

}
