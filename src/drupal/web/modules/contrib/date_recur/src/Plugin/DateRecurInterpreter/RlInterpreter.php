<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\DateRecurInterpreter;

use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\date_recur\Plugin\DateRecurInterpreterPluginBase;
use RRule\RRule;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interpreter implemented by rlanvin/php-rrule.
 *
 * @DateRecurInterpreter(
 *  id = "rl",
 *  label = @Translation("RL interpreter"),
 * )
 *
 * @ingroup RLanvinPhpRrule
 */
class RlInterpreter extends DateRecurInterpreterPluginBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  use DependencyTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new RlInterpreter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $dateFormatStorage
   *   The date format storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $dateFormatter, EntityStorageInterface $dateFormatStorage) {
    parent::__construct([], $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->dateFormatter = $dateFormatter;
    $this->dateFormatStorage = $dateFormatStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'show_start_date' => TRUE,
      'show_until' => TRUE,
      'date_format' => '',
      'show_infinite' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function interpret(array $rules, string $language, ?\DateTimeZone $timeZone = NULL): string {
    $pluginConfig = $this->getConfiguration();

    if (!in_array($language, $this->supportedLanguages())) {
      throw new \Exception('Language not supported.');
    }

    $options = [
      'locale' => $language,
      'include_start' => $pluginConfig['show_start_date'],
      'include_until' => $pluginConfig['show_until'],
      'explicit_infinite' => $pluginConfig['show_infinite'],
    ];

    $dateFormatId = $this->configuration['date_format'];
    if (!empty($dateFormatId)) {
      $dateFormat = $this->dateFormatStorage->load($dateFormatId);
      if ($dateFormat) {
        $dateFormatter = function (\DateTimeInterface $date) use ($dateFormat, $timeZone): string {
          $timeZoneString = $timeZone ? $timeZone->getName() : NULL;
          return $this->dateFormatter->format($date->getTimestamp(), (string) $dateFormat->id(), '', $timeZoneString);
        };
        $options['date_formatter'] = $dateFormatter;
      }
    }

    $strings = [];
    foreach ($rules as $rule) {
      $rrule = new RRule($rule->getParts());
      $strings[] = $rrule->humanReadable($options);
    }

    return implode(', ', $strings);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['show_start_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the start date'),
      '#default_value' => $this->configuration['show_start_date'],
    ];

    $form['show_until'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the until date'),
      '#default_value' => $this->configuration['show_until'],
    ];

    $form['show_infinite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show infinite if infinite.'),
      '#default_value' => $this->configuration['show_infinite'],
    ];

    $exampleDate = new DrupalDateTime();
    $dateFormatOptions = array_map(
      function (DateFormatInterface $dateFormat) use ($exampleDate): TranslatableMarkup {
        return $this->t('@name (@date)', [
          '@name' => $dateFormat->label(),
          '@date' => $this->dateFormatter->format($exampleDate->getTimestamp(), (string) $dateFormat->id()),
        ]);
      },
      $this->dateFormatStorage->loadMultiple()
    );
    $form['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#description' => $this->t('Date format used for start and until dates.'),
      '#default_value' => $this->configuration['date_format'],
      '#options' => $dateFormatOptions,
      '#empty_option' => $this->t('- None -'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['show_start_date'] = $form_state->getValue('show_start_date');
    $this->configuration['show_until'] = $form_state->getValue('show_until');
    $this->configuration['date_format'] = $form_state->getValue('date_format');
    $this->configuration['show_infinite'] = $form_state->getValue('show_infinite');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    /** @var string $dateFormatId */
    $dateFormatId = $this->configuration['date_format'];
    $dateFormat = $this->dateFormatStorage->load($dateFormatId);
    if ($dateFormat) {
      $this->addDependency('config', $dateFormat->getConfigDependencyName());
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function supportedLanguages(): array {
    return [
      'de',
      'en',
      'es',
      'fi',
      'fr',
      'it',
      'nl',
    ];
  }

}
