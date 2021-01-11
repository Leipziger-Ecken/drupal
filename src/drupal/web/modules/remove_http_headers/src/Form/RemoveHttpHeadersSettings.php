<?php

namespace Drupal\remove_http_headers\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\remove_http_headers\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the module settings.
 */
class RemoveHttpHeadersSettings extends ConfigFormBase {

  /**
   * The config manager service.
   *
   * @var \Drupal\remove_http_headers\Config\ConfigManager
   */
  private $configManager;

  /**
   * RemoveResponseHeaders constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\remove_http_headers\Config\ConfigManager $configManager
   *   The config manager service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConfigManager $configManager) {
    parent::__construct($configFactory);

    $this->configManager = $configManager;
  }

  /**
   * {@inheritdoc}
   *
   * @return static
   *   The created object.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('remove_http_headers.config_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'remove_http_headers.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remove_http_headers_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['headers_to_remove'] = [
      '#type' => 'textarea',
      '#title' => $this->t('HTTP headers'),
      '#description' => $this->t('Add headers that should be removed from responses, one header per line.'),
      '#default_value' => implode("\n", $this->configManager->getHeadersToRemove(TRUE)),
    ];

    $form['x_generator_info'] = [
      '#markup' => $this->t("If the header <code>X-Generator</code> is configured for removal, Drupal's default <code>&lt;meta name='Generator' value='Drupal (version N)'&gt;</code> will be removed from HTML output."),
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * We cannot add a return typehint or add a return type.
   * Because of the interface / PHPCS rule "Drupal.Commenting.FunctionComment.VoidReturn".
   *
   * @phpstan-ignore-next-line
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $headersToRemove = $this->convertHeadersToRemoveValueToArray($form_state->getValue('headers_to_remove'));

    foreach ($headersToRemove as $headerToRemove) {
      // Set error if any HTTP header contains whitespace.
      preg_match('/^[^\s]+$/', $headerToRemove, $matches);
      if (count($matches) !== 1) {
        $form_state->setErrorByName('headers_to_remove', $this->t('The format of the "HTTP headers" field is not valid.</br>Make sure every HTTP header is on a separate line.'));
        break;
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * We cannot add a return typehint or add a return type.
   * Because of the interface / PHPCS rule "Drupal.Commenting.FunctionComment.VoidReturn".
   *
   * @phpstan-ignore-next-line
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $headersToRemove = $this->convertHeadersToRemoveValueToArray($form_state->getValue('headers_to_remove'));

    $this->configManager->saveHeadersToRemoveToConfig($headersToRemove);

    parent::submitForm($form, $form_state);
  }

  /**
   * Converts the value of the headers to remove textarea to an array.
   *
   * @param string $headersToRemoveValue
   *   The value of the headers to remove textarea.
   *
   * @return array
   *   Array with item for each textarea line.
   */
  private function convertHeadersToRemoveValueToArray($headersToRemoveValue) {
    // Convert string to array and trim empty values and spaces.
    return array_filter(array_map('trim', explode(PHP_EOL, $headersToRemoveValue)));
  }

}
