<?php

namespace Drupal\htmlmail\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests basic installability of the HTML Mail module.
 *
 * @group htmlmail
 */
class HTMLMailTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['htmlmail'];

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return [
      'name' => 'HTML Mail hello',
      'description' => 'Dummy test to satisfy DrupalCI.',
      'group' => 'HTML Mail',
    ];
  }

  /**
   * Implements setUp().
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Just an empty test.
   */
  public function testHello() {
  }

}
