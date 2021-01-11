<?php

namespace Drupal\Tests\rules\Unit;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Mock class to replace the messenger service in unit tests.
 */
class TestMessenger implements MessengerInterface {

  /**
   * Array of messages.
   *
   * @var array
   */
  protected $messages = NULL;

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE) {
    if (!empty($message)) {
      $this->messages[$type] = isset($this->messages[$type]) ? $this->messages[$type] : [];
      if ($repeat || !in_array($message, $this->messages[$type])) {
        $this->messages[$type][] = $message;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addStatus($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_STATUS, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function addError($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_ERROR, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function addWarning($message, $repeat = FALSE) {
    return $this->addMessage($message, static::TYPE_WARNING, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    return $this->messages;
  }

  /**
   * {@inheritdoc}
   */
  public function messagesByType($type) {
    if (!empty($type)) {
      return isset($this->messages[$type]) ? $this->messages[$type] : [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->messages = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByType($type) {
    if (!empty($type) && isset($this->messages[$type])) {
      $this->messages[$type] = NULL;
    }
  }

}
