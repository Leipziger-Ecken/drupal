<?php

namespace Drupal\rules\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rules\Entity\ReactionRuleConfig;

/**
 * Controller methods for Reaction rules.
 */
class RulesReactionController extends ControllerBase {

  /**
   * Enables a reaction rule.
   *
   * @param \Drupal\rules\Entity\ReactionRuleConfig $rules_reaction_rule
   *   The reaction rule configuration entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the reaction rules listing page.
   */
  public function enableConfig(ReactionRuleConfig $rules_reaction_rule) {
    $rules_reaction_rule->enable()->save();

    $this->getLogger('rules')->notice('The reaction rule %label has been enabled.', [
      '%label' => $rules_reaction_rule->label(),
    ]);
    $this->messenger()->addMessage($this->t('The reaction rule %label has been enabled.', [
      '%label' => $rules_reaction_rule->label(),
    ]));

    return $this->redirect('entity.rules_reaction_rule.collection');
  }

  /**
   * Disables a reaction rule.
   *
   * @param \Drupal\rules\Entity\ReactionRuleConfig $rules_reaction_rule
   *   The reaction rule configuration entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the reaction rules listing page.
   */
  public function disableConfig(ReactionRuleConfig $rules_reaction_rule) {
    $rules_reaction_rule->disable()->save();

    $this->getLogger('rules')->notice('The reaction rule %label has been disabled.', [
      '%label' => $rules_reaction_rule->label(),
    ]);
    $this->messenger()->addMessage($this->t('The reaction rule %label has been disabled.', [
      '%label' => $rules_reaction_rule->label(),
    ]));

    return $this->redirect('entity.rules_reaction_rule.collection');
  }

}
