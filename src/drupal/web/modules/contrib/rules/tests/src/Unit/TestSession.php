<?php

namespace Drupal\Tests\rules\Unit;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Implements just the methods we need for the Rules unit tests.
 */
class TestSession implements SessionInterface {

  /**
   * Simulated session storage.
   *
   * @var array
   */
  protected $logs = [];

  /**
   * {@inheritdoc}
   */
  public function all() {
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    if (isset($this->logs[$key])) {
      return $this->logs[$key];
    }
    else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBag($name) {
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataBag() {
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
  }

  /**
   * {@inheritdoc}
   */
  public function has($name) {
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($lifetime = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function isStarted() {
  }

  /**
   * {@inheritdoc}
   */
  public function migrate($destroy = FALSE, $lifetime = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function registerBag(SessionBagInterface $bag) {
  }

  /**
   * {@inheritdoc}
   */
  public function remove($key) {
    if (isset($this->logs[$key])) {
      $return = $this->logs[$key];
      unset($this->logs[$key]);
      return $return;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array $attributes) {
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->logs[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
  }

  /**
   * {@inheritdoc}
   */
  public function start() {
  }

}
