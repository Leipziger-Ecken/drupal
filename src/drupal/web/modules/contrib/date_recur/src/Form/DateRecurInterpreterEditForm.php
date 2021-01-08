<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\date_recur\Plugin\DateRecurInterpreterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for date recur interpreter entities.
 *
 * @method \Drupal\date_recur\Entity\DateRecurInterpreter getEntity()
 */
class DateRecurInterpreterEditForm extends EntityForm {

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Date recur interpreter plugin manager.
   *
   * @var \Drupal\date_recur\Plugin\DateRecurInterpreterManagerInterface
   */
  protected $dateRecurInterpreterPluginManager;

  /**
   * Creates an instance of WorkflowStateEditForm.
   *
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   * @param \Drupal\date_recur\Plugin\DateRecurInterpreterManagerInterface $dateRecurInterpreterPluginManager
   *   Date recur interpreter plugin manager.
   */
  public function __construct(PluginFormFactoryInterface $pluginFormFactory, DateRecurInterpreterManagerInterface $dateRecurInterpreterPluginManager) {
    $this->pluginFormFactory = $pluginFormFactory;
    $this->dateRecurInterpreterPluginManager = $dateRecurInterpreterPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin_form.factory'),
      $container->get('plugin.manager.date_recur_interpreter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $dateRecurInterpreter = $this->getEntity();

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $dateRecurInterpreter->label(),
    ];

    $plugin = $dateRecurInterpreter->getPlugin();

    $key = 'configure';
    if ($plugin->hasFormClass($key)) {
      $form['configure'] = [
        '#tree' => TRUE,
      ];
      $subformState = SubformState::createForSubform($form['configure'], $form, $form_state);
      $form['configure'] += $this->pluginFormFactory
        ->createInstance($plugin, $key)
        ->buildConfigurationForm($form['configure'], $subformState);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $key = 'configure';
    $plugin = $this->getEntity()->getPlugin();
    if ($plugin->hasFormClass($key)) {
      $subformState = SubformState::createForSubform($form['configure'], $form, $form_state);
      $this->pluginFormFactory
        ->createInstance($plugin, $key)
        ->validateConfigurationForm($form['configure'], $subformState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    $key = 'configure';
    $plugin = $entity->getPlugin();
    if ($plugin->hasFormClass($key)) {
      $subformState = SubformState::createForSubform($form['configure'], $form, $form_state);
      $this->pluginFormFactory
        ->createInstance($plugin, $key)
        ->submitConfigurationForm($form['configure'], $subformState);
    }

    $result = $entity->save();
    $this->messenger()->addStatus($this->t('Saved the %label interpreter.', [
      '%label' => $entity->label(),
    ]));
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
