<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ban\BanIpManagerInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for rules module IP ban actions.
 */
abstract class RulesBanActionBase extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The ban manager.
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
   * The logger channel the action will write log messages to.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs the RulesBanActionBase object.
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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Rules logger channel.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BanIpManagerInterface $ban_manager, RequestStack $request_stack, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->banManager = $ban_manager;
    $this->requestStack = $request_stack;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ban.ip_manager'),
      $container->get('request_stack'),
      $container->get('logger.factory')->get('rules')
    );
  }

}
