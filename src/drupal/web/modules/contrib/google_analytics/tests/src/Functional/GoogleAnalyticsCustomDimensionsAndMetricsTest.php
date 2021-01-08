<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test custom dimensions and metrics functionality of Google Analytics module.
 *
 * @group Google Analytics
 *
 * @dependencies token
 */
class GoogleAnalyticsCustomDimensionsAndMetricsTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'token', 'node'];

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
      'administer nodes',
      'create article content',
    ];

    // Create node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if custom dimensions are properly added to the page.
   */
  public function testGoogleAnalyticsCustomDimensions() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);

    // Basic test if the feature works.
    $google_analytics_custom_dimension = [
      1 => [
        'index' => 1,
        'name' => 'bar1',
        'value' => 'Bar 1',
      ],
      2 => [
        'index' => 2,
        'name' => 'bar2',
        'value' => 'Bar 2',
      ],
      3 => [
        'index' => 3,
        'name' => 'bar2',
        'value' => 'Bar 3',
      ],
      4 => [
        'index' => 4,
        'name' => 'bar4',
        'value' => 'Bar 4',
      ],
      5 => [
        'index' => 5,
        'name' => 'bar5',
        'value' => 'Bar 5',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.dimension', $google_analytics_custom_dimension)->save();
    $this->drupalGet('');

    $custom_map = [];
    $custom_vars = [];
    foreach ($google_analytics_custom_dimension as $dimension) {
      $custom_map['custom_map']['dimension' . $dimension['index']] = $dimension['name'];
      $custom_vars[$dimension['name']] = $dimension['value'];
    }
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', ' . Json::encode($custom_map) . ');');
    $this->assertRaw('gtag("event", "custom", ' . Json::encode($custom_vars) . ');');

    // Test whether tokens are replaced in custom dimension values.
    $site_slogan = $this->randomMachineName(16);
    $this->config('system.site')->set('slogan', $site_slogan)->save();

    $google_analytics_custom_dimension = [
      1 => [
        'index' => 1,
        'name' => 'site_slogan',
        'value' => 'Value: [site:slogan]',
      ],
      2 => [
        'index' => 2,
        'name' => 'machine_name',
        'value' => $this->randomMachineName(16),
      ],
      3 => [
        'index' => 3,
        'name' => 'foo3',
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      4 => [
        'index' => 4,
        'name' => 'bar4',
        'value' => '0',
      ],
      5 => [
        'index' => 5,
        'name' => 'node_type',
        'value' => '[node:type]',
      ],
      // Test google_analytics_tokens().
      6 => [
        'index' => 6,
        'name' => 'current_user_role_names',
        'value' => '[current-user:role-names]',
      ],
      7 => [
        'index' => 7,
        'name' => 'current_user_role_ids',
        'value' => '[current-user:role-ids]',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.dimension', $google_analytics_custom_dimension)->save();
    $this->verbose('<pre>' . print_r($google_analytics_custom_dimension, TRUE) . '</pre>');

    // Test on frontpage.
    $this->drupalGet('');
    $this->assertRaw(Json::encode('dimension1') . ':' . Json::encode($google_analytics_custom_dimension['1']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['1']['name']) . ':' . Json::encode("Value: $site_slogan"));
    $this->assertRaw(Json::encode('dimension2') . ':' . Json::encode($google_analytics_custom_dimension['2']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['2']['name']) . ':' . Json::encode($google_analytics_custom_dimension['2']['value']));
    $this->assertNoRaw(Json::encode('dimension3') . ':' . Json::encode($google_analytics_custom_dimension['3']['name']));
    $this->assertNoRaw(Json::encode($google_analytics_custom_dimension['3']['name']) . ':' . Json::encode(''));
    $this->assertRaw(Json::encode('dimension4') . ':' . Json::encode($google_analytics_custom_dimension['4']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['4']['name']) . ':' . Json::encode('0'));
    $this->assertNoRaw(Json::encode('dimension5') . ':' . Json::encode($google_analytics_custom_dimension['5']['name']));
    $this->assertNoRaw(Json::encode($google_analytics_custom_dimension['5']['name']) . ':' . Json::encode('article'));
    $this->assertRaw(Json::encode('dimension6') . ':' . Json::encode($google_analytics_custom_dimension['6']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['6']['name']) . ':' . Json::encode(implode(',', \Drupal::currentUser()->getRoles())));
    $this->assertRaw(Json::encode('dimension7') . ':' . Json::encode($google_analytics_custom_dimension['7']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['7']['name']) . ':' . Json::encode(implode(',', array_keys(\Drupal::currentUser()->getRoles()))));

    // Test on a node.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($node->getTitle());
    $this->assertRaw(Json::encode('dimension5') . ':' . Json::encode($google_analytics_custom_dimension['5']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_dimension['5']['name']) . ':' . Json::encode('article'));
  }

  /**
   * Tests if custom metrics are properly added to the page.
   */
  public function testGoogleAnalyticsCustomMetrics() {
    $ua_code = 'UA-123456-3';
    $this->config('google_analytics.settings')->set('account', $ua_code)->save();

    // Basic test if the feature works.
    $google_analytics_custom_metric = [
      1 => [
        'index' => 1,
        'name' => 'foo1',
        'value' => '6',
      ],
      2 => [
        'index' => 2,
        'name' => 'foo2',
        'value' => '8000',
      ],
      3 => [
        'index' => 3,
        'name' => 'foo3',
        'value' => '7.8654',
      ],
      4 => [
        'index' => 4,
        'name' => 'foo4',
        'value' => '1123.4',
      ],
      5 => [
        'index' => 5,
        'name' => 'foo5',
        'value' => '5,67',
      ],
    ];

    $this->config('google_analytics.settings')->set('custom.metric', $google_analytics_custom_metric)->save();
    $this->drupalGet('');

    $custom_map = [];
    $custom_vars = [];
    foreach ($google_analytics_custom_metric as $metric) {
      $custom_map['custom_map']['metric' . $metric['index']] = $metric['name'];
      $custom_vars[$metric['name']] = (float) $metric['value'];
    }
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', ' . Json::encode($custom_map) . ');');
    $this->assertRaw('gtag("event", "custom", ' . Json::encode($custom_vars) . ');');

    // Test whether tokens are replaced in custom metric values.
    $google_analytics_custom_metric = [
      1 => [
        'index' => 1,
        'name' => 'bar1',
        'value' => '[current-user:roles:count]',
      ],
      2 => [
        'index' => 2,
        'name' => 'bar2',
        'value' => mt_rand(),
      ],
      3 => [
        'index' => 3,
        'name' => 'bar3',
        'value' => '',
      ],
      // #2300701: Custom dimensions and custom metrics not outputed on zero
      // value.
      4 => [
        'index' => 4,
        'name' => 'bar4',
        'value' => '0',
      ],
    ];
    $this->config('google_analytics.settings')->set('custom.metric', $google_analytics_custom_metric)->save();
    $this->verbose('<pre>' . print_r($google_analytics_custom_metric, TRUE) . '</pre>');

    $this->drupalGet('');
    $this->assertRaw(Json::encode('metric1') . ':' . Json::encode($google_analytics_custom_metric['1']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_metric['1']['name']) . ':');
    $this->assertRaw(Json::encode('metric2') . ':' . Json::encode($google_analytics_custom_metric['2']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_metric['2']['name']) . ':' . Json::encode($google_analytics_custom_metric['2']['value']));
    $this->assertNoRaw(Json::encode('metric3') . ':' . Json::encode($google_analytics_custom_metric['3']['name']));
    $this->assertNoRaw(Json::encode($google_analytics_custom_metric['3']['name']) . ':' . Json::encode(''));
    $this->assertRaw(Json::encode('metric4') . ':' . Json::encode($google_analytics_custom_metric['4']['name']));
    $this->assertRaw(Json::encode($google_analytics_custom_metric['4']['name']) . ':' . Json::encode(0));
  }

  /**
   * Tests if Custom Dimensions token form validation works.
   */
  public function testGoogleAnalyticsCustomDimensionsTokenFormValidation() {
    $ua_code = 'UA-123456-1';

    // Check form validation.
    $edit['google_analytics_account'] = $ua_code;
    $edit['google_analytics_custom_dimension[indexes][1][name]'] = 'current_user_name';
    $edit['google_analytics_custom_dimension[indexes][1][value]'] = '[current-user:name]';
    $edit['google_analytics_custom_dimension[indexes][2][name]'] = 'current_user_edit_url';
    $edit['google_analytics_custom_dimension[indexes][2][value]'] = '[current-user:edit-url]';
    $edit['google_analytics_custom_dimension[indexes][3][name]'] = 'user_name';
    $edit['google_analytics_custom_dimension[indexes][3][value]'] = '[user:name]';
    $edit['google_analytics_custom_dimension[indexes][4][name]'] = 'term_name';
    $edit['google_analytics_custom_dimension[indexes][4][value]'] = '[term:name]';
    $edit['google_analytics_custom_dimension[indexes][5][name]'] = 'term_tid';
    $edit['google_analytics_custom_dimension[indexes][5][value]'] = '[term:tid]';

    $this->drupalPostForm('admin/config/system/google-analytics', $edit, $this->t('Save configuration'));

    $this->assertRaw($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 1]), '@invalid-tokens' => implode(', ', ['[current-user:name]'])]));
    $this->assertRaw($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 2]), '@invalid-tokens' => implode(', ', ['[current-user:edit-url]'])]));
    $this->assertRaw($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => $this->t('Custom dimension value #@index', ['@index' => 3]), '@invalid-tokens' => implode(', ', ['[user:name]'])]));
    // BUG #2037595
    //$this->assertNoRaw($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 4]), '@invalid-tokens' => implode(', ', ['[term:name]'])]));
    //$this->assertNoRaw($this->t('The %element-title is using the following forbidden tokens with personal identifying information: @invalid-tokens.', ['%element-title' => t('Custom dimension value #@index', ['@index' => 5]), '@invalid-tokens' => implode(', ', ['[term:tid]'])]));
  }

}
