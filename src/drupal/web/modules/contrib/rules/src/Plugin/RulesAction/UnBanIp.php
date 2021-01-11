<?php

namespace Drupal\rules\Plugin\RulesAction;

/**
 * Provides the 'Remove the ban on an IP address' action.
 *
 * @RulesAction(
 *   id = "rules_unban_ip",
 *   label = @Translation("Remove the ban on an IP address"),
 *   category = @Translation("Ban"),
 *   provider = "ban",
 *   context_definitions = {
 *     "ip" = @ContextDefinition("string",
 *       label = @Translation("IP Address"),
 *       description = @Translation("Removes the ban on an IP address using the Ban Module. If no IP is provided, the current user IP is used."),
 *       default_value = NULL,
 *       required = FALSE
 *     )
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 * @todo We should maybe use a dedicated data type for the ip address, as we
 * do in Drupal 7.
 */
class UnBanIp extends RulesBanActionBase {

  /**
   * Executes the UnBanIP action with the given context.
   *
   * @param string $ip
   *   (optional) The IP address for which the ban should be removed.
   */
  protected function doExecute($ip = NULL) {
    if (!isset($ip)) {
      $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    $this->banManager->unbanIp($ip);
    $this->logger->notice('Removed ban on IP address %ip', ['%ip' => $ip]);
  }

}
