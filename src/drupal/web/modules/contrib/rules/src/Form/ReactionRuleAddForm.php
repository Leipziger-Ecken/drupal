<?php

namespace Drupal\rules\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Core\RulesConfigurableEventHandlerInterface;
use Drupal\rules\Core\RulesEventManager;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add a reaction rule.
 */
class ReactionRuleAddForm extends RulesComponentFormBase {

  /**
   * The Rules event manager.
   *
   * @var \Drupal\rules\Core\RulesEventManager
   */
  protected $eventManager;

  /**
   * The entity type bundle information manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructs a new reaction rule form.
   *
   * @param \Drupal\rules\Engine\ExpressionManagerInterface $expression_manager
   *   The expression manager.
   * @param \Drupal\rules\Core\RulesEventManager $event_manager
   *   The Rules event manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity type bundle information manager.
   */
  public function __construct(ExpressionManagerInterface $expression_manager, RulesEventManager $event_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    parent::__construct($expression_manager);
    $this->eventManager = $event_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rules_expression'),
      $container->get('plugin.manager.rules_event'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $event_definitions = $this->eventManager->getGroupedDefinitions();
    $options = [];
    foreach ($event_definitions as $group => $definitions) {
      foreach ($definitions as $id => $definition) {
        $options[$group][$id] = $definition['label'];
      }
    }

    $form['#entity_builders'][] = '::entityBundleBuilder';
    $form['selection'] = [
      '#type' => 'details',
      '#title' => $this->t('Event selection'),
      '#open' => TRUE,
    ];

    $form['selection']['events'] = [
      '#tree' => TRUE,
    ];

    // Selection of an event will trigger an Ajax request to see if this is an
    // entity event; if so, present a select element to choose a bundle type.
    $form['selection']['events'][]['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('React on event'),
      '#options' => $options,
      '#description' => $this->t('Rule evaluation is triggered whenever the selected event occurs.'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'entity-bundle-restriction',
        'callback' => '::bundleSelectCallback',

      ],
    ];

    // Empty container to hold the bundle selection element, if available
    // for the event chosen above.
    $form['selection']['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'entity-bundle-restriction',
      ],
    ];

    $event_name = $form_state->getValue(['events', 0, 'event_name']);
    // On form reload via Ajax, the $event_name will be set.
    if (!empty($event_name)) {
      // Add a non-required select element "Restrict by type" to choose from
      // all the bundles defined for the entity type.
      $event_definition = $this->eventManager->getDefinition($event_name);
      $handler_class = $event_definition['class'];
      if (is_subclass_of($handler_class, RulesConfigurableEventHandlerInterface::class)) {
        // We have bundles ...
        $bundles = $this->entityBundleInfo->getBundleInfo($event_definition['entity_type_id']);
        // Transform the $bundles array into a form suitable for select options.
        array_walk($bundles, function (&$value, $key) {
          $value = $value['label'];
        });

        // Bundle selections for this entity type.
        $form['selection']['container']['bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Restrict by type'),
          '#empty_option' => '- None -',
          '#empty_value' => 'notselected',
          '#options' => $bundles,
          '#description' => $this->t('If you need to filter for multiple values, either add multiple events or use the "Entity is of bundle" condition. These options are available after saving this form.'),
        ];
      }
    }

    return $form + parent::form($form, $form_state);
  }

  /**
   * Ajax callback for the entity bundle restriction select element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function bundleSelectCallback(array $form, FormStateInterface $form_state) {
    // Replace the entire container placholder element.
    return $form['selection']['container'];
  }

  /**
   * Callback method for the #entity_builder form property.
   *
   * Used to qualify the selected event name with a bundle suffix.
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity whose form is being built.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function entityBundleBuilder($entity_type, ConfigEntityInterface $entity, array $form, FormStateInterface $form_state) {
    $bundle = $form_state->getValue('bundle');
    if (!empty($bundle) && $bundle != 'notselected') {
      $event_name = $form_state->getValue(['events', 0, 'event_name']);
      // Fully-qualify the event name if a bundle was selected.
      $form_state->setValue(['events', 0, 'event_name'], $event_name . '--' . $bundle);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Reaction rule %label has been created.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.rules_reaction_rule.edit_form', [
      'rules_reaction_rule' => $this->entity->id(),
    ]);
  }

}
