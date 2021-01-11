<?php

namespace Drupal\rules\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Logs rules log entries in the available loggers.
 */
class RulesDebugLoggerChannel extends LoggerChannel {

  /**
   * The Rules debug log.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $rulesDebugLog;

  /**
   * A configuration object with rules settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Creates RulesDebugLoggerChannel object.
   *
   * @param \Psr\Log\LoggerInterface $rules_debug_log
   *   The Rules debug log.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory instance.
   */
  public function __construct(LoggerInterface $rules_debug_log, ConfigFactoryInterface $config_factory) {
    parent::__construct('rules_debug');
    $this->rulesDebugLog = $rules_debug_log;
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Parent log() function is overridden to use microtime() for Rules execution
   * times and to prepare additional variables to pass to the logger. These
   * variables hold the extra call parameters used by RulesDebugLog for
   * backtracing. These are passed in via $context[] where they will be ignored
   * by any module that doesn't know about them ...
   */
  public function log($level, $message, array $context = []) {
    // Log debugging information only if the debug_log.enabled setting is
    // enabled. Otherwise exit immediately.
    $config = $this->config->get('rules.settings');
    if (!$config->get('debug_log.enabled')) {
      return;
    }

    if ($this->callDepth == self::MAX_CALL_DEPTH) {
      return;
    }
    $this->callDepth++;

    // Merge in defaults normally provided by the parent LoggerChannel class.
    $context += [
      'channel' => $this->channel,
      'link' => '',
      'uid' => 0,
      'request_uri' => '',
      'referer' => '',
      'ip' => '',
      'timestamp' => microtime(TRUE),
    ];

    // Some context values are only available when in a request context.
    if ($this->requestStack && ($request = $this->requestStack->getCurrentRequest())) {
      $context['request_uri'] = $request->getUri();
      $context['referer'] = $request->headers->get('Referer', '');
      $context['ip'] = $request->getClientIP();
      if ($this->currentUser) {
        $context['uid'] = $this->currentUser->id();
      }
    }

    // Extract the Rules-specific defaults from $context.
    $element = isset($context['element']) ? $context['element'] : NULL;
    $scope = isset($context['scope']) ? $context['scope'] : NULL;
    $path = isset($context['path']) ? $context['path'] : NULL;

    if (!empty($element)) {
      // Need to know if we're in a Reaction Rule or a Rules Component, and need
      // to be able to get a reference to that specific entity. For now we just
      // assume Reaction Rule until we know how to do this better.
      $path = $element->getRoot()->getLabel() ?
        Url::fromRoute('entity.rules_reaction_rule.edit_form.expression.edit', [
          'rules_reaction_rule' => $element->getRoot()->getPluginId(),
          'uuid' => $element->getUuid(),
        ])->toString() :
        NULL;
    }

    // Pack $element, $scope, and $path into the context array so as to forward
    // them to the logger.channel.rules_debug logger channel for logging.
    $context += [
      'element' => $element,
      'scope' => $scope,
      'path' => $path,
    ];
    // Now, write the logs to the RulesDebugLog to store in memory. This is the
    // principal reason for this class! Keep the array structure we used in D7
    // so we don't have to re-write the theming functions and JavaScript too
    // much. We added keys for D8 so we don't have to guess the contents of the
    // array elemens from their order index.

    $rfc_level = $level;
    if (is_string($level)) {
      // Convert to integer equivalent for consistency with RFC 5424.
      $rfc_level = $this->levelTranslation[$level];
    }

    // A lesser RFC level is MORE severe, so we want to test if the RFC level
    // of the message is LESS than or equal to the threshold level setting.
    if ($this->levelTranslation[$config->get('debug_log.log_level')] >= $rfc_level) {
      $this->rulesDebugLog->log($level, $message, $context);

      // Support any loggers added through the API, just because we can (most
      // of this is inherited from parent LoggerChannel class).
      foreach ($this->sortLoggers() as $logger) {
        $logger->log($rfc_level, $message, $context);
      }
    }

    $this->callDepth--;
  }

}
