<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test status messages functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsStatusMessagesTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'google_analytics_test'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer google analytics',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if status messages tracking is properly added to the page.
   */
  public function testGoogleAnalyticsStatusMessages() {
    $ua_code = 'UA-123456-4';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Enable logging of errors only.
    $this->config('google_analytics.settings')->set('track.messages', ['error' => 'error'])->save();

    $this->drupalPostForm('user/login', [], $this->t('Log in'));
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Username field is required."});');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Password field is required."});');

    // Testing this drupal_set_message() requires an extra test module.
    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertNoRaw('gtag("event", "Status message", {"event_category":"Messages","event_label":"Example status message."});');
    $this->assertNoRaw('gtag("event", "Warning message", {"event_category":"Messages","event_label":"Example warning message."});');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message."});');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message with html tags and link."});');

    // Enable logging of status, warnings and errors.
    $this->config('google_analytics.settings')->set('track.messages', [
      'status' => 'status',
      'warning' => 'warning',
      'error' => 'error',
    ])->save();

    $this->drupalGet('google-analytics-test/drupal-messenger-add-message');
    $this->assertRaw('gtag("event", "Status message", {"event_category":"Messages","event_label":"Example status message."});');
    $this->assertRaw('gtag("event", "Warning message", {"event_category":"Messages","event_label":"Example warning message."});');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message."});');
    $this->assertRaw('gtag("event", "Error message", {"event_category":"Messages","event_label":"Example error message with html tags and link."});');
  }

}
