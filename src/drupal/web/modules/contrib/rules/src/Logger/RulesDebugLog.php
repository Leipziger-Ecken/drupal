<?php

namespace Drupal\rules\Logger;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Logger that stores Rules debug logs with the session service.
 *
 * This logger stores an array of Rules debug logs in the session under
 * the attribute named 'rules_debug_log'.
 */
class RulesDebugLog implements LoggerInterface {
  use LoggerTrait;
  use StringTranslationTrait;

  /**
   * Local storage of log entries.
   *
   * @var array
   */
  protected $logs = [];

  /**
   * The session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a RulesDebugLog object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session service.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    // Remove any backtraces since they may contain an unserializable variable.
    unset($context['backtrace']);

    $localCopy = $this->session->get('rules_debug_log', []);

    // Append the new log to the $localCopy array.
    // In D7:
    // @code
    //   logs[] = [$msg, $args, $priority, microtime(TRUE), $scope, $path];
    // @endcode
    $localCopy[] = [
      'message' => $message,
      'context' => $context,
      /** @var \Psr\Log\LogLevel $level */
      'level' => $level,
      'timestamp' => $context['timestamp'],
      'scope' => $context['scope'],
      'path' => $context['path'],
    ];

    // Write the $localCopy array back into the session;
    // it now includes the new log.
    $this->session->set('rules_debug_log', $localCopy);
  }

  /**
   * Returns a structured array of log entries.
   *
   * @return array
   *   Array of stored log entries, keyed by an integer log line number. Each
   *   element of the array contains the following keys:
   *   - message: The log message, optionally with FormattedMarkup placeholders.
   *   - context: An array of message placeholder replacements.
   *   - level: \Psr\Log\LogLevel level.
   *   - timestamp: Microtime timestamp in float format.
   *   - scope: TRUE if there are nested logs for this entry, FALSE if this is
   *     the last of the nested entries.
   *   - path: Path to edit this component.
   */
  public function getLogs() {
    return (array) $this->session->get('rules_debug_log');
  }

  /**
   * Clears the logs entries from the storage.
   */
  public function clearLogs() {
    $this->session->remove('rules_debug_log');
  }

  /**
   * Renders the whole log.
   *
   * @return string
   *   An string already rendered to HTML.
   */
  public function render() {
    $build = $this->build();
    return \Drupal::service('renderer')->renderPlain($build);
  }

  /**
   * Assembles the entire log into a render array.
   *
   * @return array
   *   A Drupal render array.
   */
  public function build() {
    $this->logs = $this->getLogs();

    if (count($this->logs) == 0) {
      // Nothing to render.
      return [];
    }
    // Container for all log entries.
    $build = [
      '#type' => 'details',
      // @codingStandardsIgnoreStart
      '#title' => $this->t('Rules evaluation log') . '<span class="rules-debug-open-all">-Open all-</span>',
      // @codingStandardsIgnoreEnd
      '#attributes' => ['class' => ['rules-debug-log']],
    ];

    $line = 0;
    while (isset($this->logs[$line])) {
      // Each event is in its own 'details' wrapper so the details of
      // evaluation may be opened or closed.
      $build[$line] = [
        '#type' => 'details',
        // @codingStandardsIgnoreStart
        '#title' => $this->t($this->logs[$line]['message'], $this->logs[$line]['context']),
        // @codingStandardsIgnoreEnd
      ];
      // $line is modified inside renderHelper().
      $thisline = $line;
      $build[$thisline][] = $this->renderHelper($line);
      $line++;
    }

    return $build;
  }

  /**
   * Renders the log of one event invocation.
   *
   * Called recursively, consuming all the log lines for this event.
   */
  public function renderHelper(&$line = 0) {
    $build = [];
    $startTime = $this->logs[$line]['timestamp'];
    while ($line < count($this->logs)) {
      if ($build && !empty($this->logs[$line]['scope'])) {
        // This next entry stems from another evaluated set so we create a
        // new container for its log messages then fill that container with
        // a recursive call to renderHelper().
        $link = NULL;
        if (isset($this->logs[$line]['path'])) {
          $link = Link::fromTextAndUrl($this->t('edit'), Url::fromUserInput('/' . $this->logs[$line]['path']))->toString();
        }
        $build[$line] = [
          '#type' => 'details',
          // @codingStandardsIgnoreStart
          '#title' => $this->t($this->logs[$line]['message'], $this->logs[$line]['context']) . ' [' . $link . ']',
          // @codingStandardsIgnoreEnd
        ];
        $thisline = $line;
        $build[$thisline][] = $this->renderHelper($line);
      }
      else {
        // This next entry is a leaf of the evaluated set so we just have to
        // add the details of the log entry.
        $link = NULL;
        if (isset($this->logs[$line]['path']) && !isset($this->logs[$line]['scope'])) {
          $link = ['title' => $this->t('edit'), 'url' => Url::fromUserInput('/' . $this->logs[$line]['path'])];
        }
        $build[$line] = [
          '#theme' => 'rules_debug_log_element',
          '#starttime' => $startTime,
          '#timestamp' => $this->logs[$line]['timestamp'],
          '#level' => $this->logs[$line]['level'],
          // @codingStandardsIgnoreStart
          '#text' => $this->t($this->logs[$line]['message'], $this->logs[$line]['context']),
          // @codingStandardsIgnoreEnd
          '#link' => $link,
        ];

        if (isset($this->logs[$line]['scope']) && !$this->logs[$line]['scope']) {
          // This was the last log entry of this set.
          return [
            '#theme' => 'item_list',
            '#items' => $build,
          ];
        }
      }
      $line++;
    }

    return [
      '#theme' => 'item_list',
      '#items' => $build,
    ];
  }

}
