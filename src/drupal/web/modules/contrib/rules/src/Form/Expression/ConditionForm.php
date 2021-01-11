<?php

namespace Drupal\rules\Form\Expression;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rules\Context\Form\ContextFormTrait;
use Drupal\rules\Core\ConditionManager;
use Drupal\rules\Engine\ConditionExpressionInterface;
use Drupal\rules\Ui\RulesUiHandlerTrait;

/**
 * UI form for adding/editing a Rules condition.
 */
class ConditionForm implements ExpressionFormInterface {
  use ContextFormTrait;
  use StringTranslationTrait;
  use RulesUiHandlerTrait;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\rules\Core\ConditionManager
   */
  protected $conditionManager;

  /**
   * The condition expression that is edited in the form.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionInterface
   */
  protected $conditionExpression;

  /**
   * Creates a new object of this class.
   */
  public function __construct(ConditionExpressionInterface $condition_expression, ConditionManager $condition_manager) {
    $this->conditionManager = $condition_manager;
    $this->conditionExpression = $condition_expression;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $condition_id = $form_state->get('condition_id');

    $configuration = $this->conditionExpression->getConfiguration();
    if (empty($condition_id) && !empty($configuration['condition_id'])) {
      $condition_id = $configuration['condition_id'];
      $form_state->set('condition_id', $condition_id);
    }

    // Step 1 of the multistep form.
    if (!$condition_id) {
      $condition_definitions = $this->conditionManager->getGroupedDefinitions();
      $options = [];
      foreach ($condition_definitions as $group => $definitions) {
        foreach ($definitions as $id => $definition) {
          $options[$group][$id] = $definition['label'];
        }
      }

      $form['condition_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Condition'),
        '#options' => $options,
        '#required' => TRUE,
      ];
      $form['continue'] = [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
        '#name' => 'continue',
        // Only validate the selected condition in the first step.
        '#limit_validation_errors' => [['condition_id']],
        '#submit' => [static::class . '::submitFirstStep'],
      ];

      return $form;
    }

    // Step 2 of the form.
    /** @var \Drupal\rules\Core\RulesConditionInterface $condition */
    $condition = $this->conditionManager->createInstance($condition_id);

    $form['summary'] = [
      '#type' => 'details',
      '#title' => $this->t('Summary'),
    ];
    $form['summary']['description'] = [
      '#type' => 'container',
      '#plain_text' => $this->t('Condition: @summary', ['@summary' => $condition->summary()]),
      '#attributes' => ['class' => ['form-item']],
    ];

    $context_definitions = $condition->getContextDefinitions();
    if (!empty($context_definitions)) {
      $form['context_definitions'] = [
        '#type' => 'details',
        '#title' => $this->t('Context variables'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      foreach ($context_definitions as $context_name => $context_definition) {
        $form = $this->buildContextForm($form, $form_state, $context_name, $context_definition, $configuration);
      }
    }

    $provides_definitions = $condition->getProvidedContextDefinitions();
    if (!empty($provides_definitions)) {
      $form['provides'] = [
        '#type' => 'details',
        '#title' => $this->t('Provided variables'),
        '#description' => $this->t('Adjust the name of provided variables, but note that renaming of already utilized variables invalidates the existing uses.'),
        '#open' => TRUE,
      ];
      foreach ($provides_definitions as $provides_name => $provides_definition) {
        $form = $this->buildProvidedContextForm($form, $form_state, $provides_name, $provides_definition, $configuration);
      }
    }

    $form['negate_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Negate'),
    ];

    $form['negate_wrapper']['negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate this condition'),
      '#default_value' => $configuration['negate'] ?: 0,
      '#description' => $this->t('If checked, the condition result is negated such that it returns TRUE if it evaluates to FALSE.'),
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => 'save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    // Only if there is a condition selected already we can validate something.
    if ($form_state->get('condition_id')) {
      // Invoke the submission handler which will setup the expression being
      // edited in the form. That way the expression is ready for other
      // validation handlers.
      $this->submitForm($form, $form_state);
    }
  }

  /**
   * Submit callback: save the selected condition in the first step.
   */
  public static function submitFirstStep(array &$form, FormStateInterface $form_state) {
    $form_state->set('condition_id', $form_state->getValue('condition_id'));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $condition_id = $form_state->get('condition_id');
    // Nothing to do as long as the first step is not completed.
    if (!$condition_id) {
      return;
    }

    $condition_definition = $this->conditionManager->getDefinition($condition_id);
    $context_config = $this->getContextConfigFromFormValues($form_state, $condition_definition['context_definitions']);

    // Rename provided variables, if any.
    if ($provided_variables = $form_state->getValue('provides')) {
      foreach ($provided_variables as $provides_name => $provides_context) {
        // Do this only on rename.
        if ($provides_name !== $provides_context['name']) {
          \Drupal::messenger()->addWarning("providing '" . $provides_name . "' as '" . $provides_context['name'] . "'");
          $context_config->provideAs($provides_name, $provides_context['name']);
        }
      }
    }

    $configuration = $context_config->toArray();
    $configuration['condition_id'] = $form_state->get('condition_id');
    $configuration['negate'] = $form_state->getValue('negate');
    $this->conditionExpression->setConfiguration($configuration);
  }

}
