<?php

namespace Drupal\rules\Form\Expression;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Engine\RuleExpressionInterface;

/**
 * Form view structure for rule expressions.
 *
 * @see \Drupal\rules\Plugin\RulesExpression\RuleExpression
 */
class RuleExpressionForm implements ExpressionFormInterface {
  use ExpressionFormTrait;

  /**
   * The rule expression object this form is for.
   *
   * @var \Drupal\rules\Engine\RuleExpressionInterface
   */
  protected $rule;

  /**
   * Creates a new object of this class.
   */
  public function __construct(RuleExpressionInterface $rule) {
    $this->rule = $rule;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $conditions_form_handler = $this->rule->getConditions()->getFormHandler();
    $form = $conditions_form_handler->form($form, $form_state);

    $actions_form_handler = $this->rule->getActions()->getFormHandler();
    $form = $actions_form_handler->form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->rule->getConditions()->getFormHandler()->submitForm($form, $form_state);
    $this->rule->getActions()->getFormHandler()->submitForm($form, $form_state);
  }

}
