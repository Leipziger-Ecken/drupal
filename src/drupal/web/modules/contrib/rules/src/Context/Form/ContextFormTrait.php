<?php

namespace Drupal\rules\Context\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinitionInterface;
use Drupal\rules\Context\DataProcessorManagerTrait;

/**
 * Provides form logic for handling contexts when configuring an expression.
 */
trait ContextFormTrait {
  use DataProcessorManagerTrait;

  /**
   * Provides the form part for a context parameter.
   */
  public function buildContextForm(array $form, FormStateInterface $form_state, $context_name, ContextDefinitionInterface $context_definition, array $configuration) {
    $form['context_definitions'][$context_name] = [
      '#type' => 'fieldset',
      '#title' => $context_definition->getLabel(),
    ];
    $form['context_definitions'][$context_name]['description'] = [
      '#markup' => $context_definition->getDescription(),
    ];

    // If the form has been submitted already take the mode from the submitted
    // value, otherwise check for restriction setting, then check existing
    // configuration, and if that does not exist default to the "input" mode.
    $mode = $form_state->get('context_' . $context_name);
    if (!$mode) {
      if ($mode = $context_definition->getAssignmentRestriction()) {
        // If there is an assignmentRestriction value, use this for mode.
      }
      elseif (isset($configuration['context_mapping'][$context_name])) {
        $mode = ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR;
      }
      else {
        $mode = ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT;
      }
      $form_state->set('context_' . $context_name, $mode);
    }

    $title = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? $this->t('Data selector') : $this->t('Value');

    if (isset($configuration['context_values'][$context_name])) {
      $default_value = $configuration['context_values'][$context_name];
    }
    elseif (isset($configuration['context_mapping'][$context_name])) {
      $default_value = $configuration['context_mapping'][$context_name];
    }
    else {
      $default_value = $context_definition->getDefaultValue();
    }
    $form['context_definitions'][$context_name]['setting'] = [
      '#type' => 'textfield',
      '#title' => $title,
      '#required' => $context_definition->isRequired(),
      '#default_value' => $default_value,
    ];

    $element = &$form['context_definitions'][$context_name]['setting'];

    if ($mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR) {
      $element['#description'] = $this->t("The data selector helps you drill down into the available data. <em>To make entity fields appear in the data selector, you may have to use the condition 'entity has field' (or 'content is of type').</em> More useful tips about data selection is available in <a href=':url'>the online documentation</a>.", [
        ':url' => 'https://www.drupal.org/node/1300042',
      ]);

      $url = $this->getRulesUiHandler()->getUrlFromRoute('autocomplete', []);
      $element['#attributes']['class'][] = 'rules-autocomplete';
      $element['#attributes']['data-autocomplete-path'] = $url->toString();
      $element['#attached']['library'][] = 'rules/rules.autocomplete';
    }
    elseif ($context_definition->isMultiple()) {
      $element['#type'] = 'textarea';
      // @todo Get a description for possible values that can be filled in.
      $element['#description'] = $this->t('Enter one value per line for this multi-valued context.');

      // Glue the list of values together as one item per line in the text area.
      if (is_array($default_value)) {
        $element['#default_value'] = implode("\n", $default_value);
      }
    }

    // If the context is not restricted to one mode or the other then provide a
    // button to switch between the two modes.
    if (empty($context_definition->getAssignmentRestriction())) {
      $value = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? $this->t('Switch to the direct input mode') : $this->t('Switch to data selection');
      $form['context_definitions'][$context_name]['switch_button'] = [
        '#type' => 'submit',
        '#name' => 'context_' . $context_name,
        '#attributes' => ['class' => ['rules-switch-button']],
        '#parameter' => $context_name,
        '#value' => $value,
        '#submit' => [static::class . '::switchContextMode'],
        // Do not validate!
        '#limit_validation_errors' => [],
      ];
    }

    return $form;
  }

  /**
   * Provides the form part for a 'provided' context parameter.
   */
  public function buildProvidedContextForm(array $form, FormStateInterface $form_state, $provides_name, ContextDefinitionInterface $provides_definition, array $configuration) {
    if (isset($configuration['provides_mapping'][$provides_name])) {
      $default_name = $configuration['provides_mapping'][$provides_name];
    }
    else {
      $default_name = $provides_name;
    }

    $form['provides'][$provides_name] = [
      '#type' => 'fieldset',
      '#title' => $provides_definition->getLabel(),
    ];

    $form['provides'][$provides_name]['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Variable name'),
      '#description' => $this->t('The variable name must contain only lowercase letters, numbers, and underscores and must be unique in the current scope.'),
      '#required' => TRUE,
      '#default_value' => $default_name,
    ];

    return $form;
  }

  /**
   * Creates a context config object from the submitted form values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state containing the submitted values.
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   The context definitions of the plugin.
   *
   * @return \Drupal\rules\Context\ContextConfig
   *   The context config object populated with context mappings/values.
   */
  protected function getContextConfigFromFormValues(FormStateInterface $form_state, array $context_definitions) {
    $context_config = ContextConfig::create();
    if ($form_state->hasValue('context_definitions')) {
      foreach ($form_state->getValue('context_definitions') as $context_name => $value) {
        if ($form_state->get("context_$context_name") == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR) {
          $context_config->map($context_name, $value['setting']);
        }
        else {
          // Each line of the textarea is one value for 'multiple' contexts.
          if ($context_definitions[$context_name]->isMultiple()) {
            // Textareas should always have \r\n line breaks, but for more
            // robust parsing we should also accommodate just \n or just \r.
            //
            // Additionally, we want to remove leading and trailing whitespace
            // from each line, and discard any empty lines.
            $values = preg_split('/\s*\R\s*/', $value['setting'], NULL, PREG_SPLIT_NO_EMPTY);
            $context_config->setValue($context_name, $values);
          }
          else {
            $context_config->setValue($context_name, $value['setting']);
          }
          // For now, always add in the token context processor if it's present.
          // @todo Improve this in https://www.drupal.org/node/2804035.
          if ($this->getDataProcessorManager()->getDefinition('rules_tokens')) {
            $context_config->process($context_name, 'rules_tokens');
          }
        }
      }
    }

    return $context_config;
  }

  /**
   * Submit callback: switch a context to data selecor or direct input mode.
   */
  public static function switchContextMode(array &$form, FormStateInterface $form_state) {
    $element_name = $form_state->getTriggeringElement()['#name'];
    $mode = $form_state->get($element_name);
    $switched_mode = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT : ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR;
    $form_state->set($element_name, $switched_mode);

    $form_state->setRebuild();
  }

}
