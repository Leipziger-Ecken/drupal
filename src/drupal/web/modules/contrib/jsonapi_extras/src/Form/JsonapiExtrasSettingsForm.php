<?php

namespace Drupal\jsonapi_extras\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure JSON:API settings for this site.
 */
class JsonapiExtrasSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $routerBuilder;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $router_builder
   *   The router builder to rebuild menus after saving config entity.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilder $router_builder) {
    parent::__construct($config_factory);
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jsonapi_extras.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jsonapi_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jsonapi_extras.settings');

    $form['path_prefix'] = [
      '#title' => $this->t('Path prefix'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#field_prefix' => '/',
      '#description' => $this->t('The path prefix for JSON:API.'),
      '#default_value' => $config->get('path_prefix'),
    ];

    $form['include_count'] = [
      '#title' => $this->t('Include count in collection queries'),
      '#type' => 'checkbox',
      '#description' => $this->t('If activated, all collection responses will return a total record count for the provided query.'),
      '#default_value' => $config->get('include_count'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($path_prefix = $form_state->getValue('path_prefix')) {
      $this->config('jsonapi_extras.settings')
        ->set('path_prefix', trim($path_prefix, '/'))
        ->save();
    }

    $this->config('jsonapi_extras.settings')
      ->set('include_count', $form_state->getValue('include_count'))
      ->save();

    // Rebuild the router.
    $this->routerBuilder->setRebuildNeeded();

    parent::submitForm($form, $form_state);
  }

}
