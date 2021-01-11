<?php

namespace Drupal\rules_test\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Logger\RulesDebugLoggerChannel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action writing an error to the Rules debug logger channel.
 *
 * @RulesAction(
 *   id = "rules_test_debug_log",
 *   label = @Translation("Test action debug logging"),
 *   category = @Translation("Tests"),
 *   context_definitions = {
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message to log"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class TestDebugLogAction extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * Rules debug logger instance.
   *
   * @var \Drupal\rules\Logger\RulesDebugLoggerChannel
   */
  protected $logger;

  /**
   * Constructs a TestDebugLogAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rules\Logger\RulesDebugLoggerChannel $logger
   *   Rules debug logger object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RulesDebugLoggerChannel $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('logger.channel.rules_debug')
    );
  }

  /**
   * Writes an error message to the debug log.
   *
   * @param string $message
   *   Message string that should be logged. Defaults to "action called".
   */
  protected function doExecute($message = NULL) {
    if (empty($message)) {
      $message = 'action called';
    }
    $this->logger->error($message);
  }

}
