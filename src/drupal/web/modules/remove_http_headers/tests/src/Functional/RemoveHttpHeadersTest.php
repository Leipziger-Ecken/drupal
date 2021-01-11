<?php

declare(strict_types=1);

namespace Drupal\Tests\remove_http_headers\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Functional tests for Remove HTTP Headers module.
 *
 * @coversDefaultClass \Drupal\remove_http_headers\StackMiddleware\RemoveHttpHeadersMiddleware
 * @group remove_http_headers
 */
class RemoveHttpHeadersTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * The path to the module settings form.
   */
  protected const SETTINGS_FORM_PATH = 'admin/config/system/remove-http-headers';

  /**
   * HTML markup of the Generator metatag.
   */
  protected const GENERATOR_METATAG_MARKUP = '<meta name="Generator" content="Drupal';

  /**
   * Array of modules to enable.
   *
   * @var array
   */
  protected static $modules = ['remove_http_headers'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->container->get('config.factory', ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE);
    $admin = $this->drupalCreateUser(['remove_http_headers settings access']);
    $this->drupalLogin($admin);
  }

  /**
   * Submits the modules' settings form with given form content.
   *
   * @param array $form
   *   The form content.
   */
  protected function submitModuleSettingsForm(array $form): void {
    $this->drupalGet(self::SETTINGS_FORM_PATH);
    $this->submitForm($form, $this->t('Save configuration')->render());
  }

  /**
   * Ensure HTTP headers and meta are present when no removals configured.
   *
   * @covers ::handle
   */
  public function testRemoveNoHeaders(): void {
    $form['headers_to_remove'] = '';
    $this->submitModuleSettingsForm($form);

    // Verify config form reflects the change.
    $this->assertSession()->fieldValueEquals('headers_to_remove', '');

    // Verify saved configuration reflects the change.
    $config = $this->configFactory->get('remove_http_headers.settings')->get('headers_to_remove');
    $this->assertEquals([], $config, 'The configuration option was properly saved.');

    // Headers which we should expect Drupal to return.
    $headersThatShouldExist = [
      'X-Generator',
      'X-Drupal-Cache-Tags',
      'X-Drupal-Dynamic-Cache',
    ];
    foreach ($headersThatShouldExist as $header) {
      $this->assertNotEmpty(
        $this->drupalGetHeader($header),
        $this->t('HTTP header @header is not present in response.', ['@header' => $header])->render()
      );
    }

    // Default behaviour is to include meta name=Generator.
    $this->assertSession()->responseContains(static::GENERATOR_METATAG_MARKUP);
  }

  /**
   * Ensure default HTTP headers and meta are removed when configured.
   *
   * @covers ::handle
   */
  public function testRemoveSomeHeaders(): void {
    $headersToRemove = [
      'X-Generator',
      'X-Drupal-Cache-Tags',
      'X-Drupal-Dynamic-Cache',
    ];
    $form['headers_to_remove'] = implode("\n", $headersToRemove);
    $this->submitModuleSettingsForm($form);

    // Verify config form reflects the change.
    $this->assertSession()->fieldValueEquals('headers_to_remove', implode("\n", $headersToRemove));

    // Verify saved configuration reflects the change.
    $config = $this->configFactory->get('remove_http_headers.settings')->get('headers_to_remove');
    $this->assertEquals($headersToRemove, $config, 'The configuration option was properly saved.');

    foreach ($headersToRemove as $header) {
      $this->assertEmpty(
        $this->drupalGetHeader($header),
        $this->t('HTTP header @header is not present in response.', ['@header' => $header])->render());
    }

    // X-Generator is set, so meta name=Generator should not be present.
    $this->assertSession()->responseNotContains(static::GENERATOR_METATAG_MARKUP);
  }

}
