<?php

namespace Drupal\rules\Plugin\RulesAction;

/**
 * Provides the 'Ban IP' action.
 *
 * @RulesAction(
 *   id = "rules_ban_ip",
 *   label = @Translation("Ban an IP address"),
 *   category = @Translation("Ban"),
 *   provider = "ban",
 *   context_definitions = {
 *     "ip" = @ContextDefinition("string",
 *       label = @Translation("IP Address"),
 *       description = @Translation("Ban an IP address using the Ban Module. If no IP is provided, the current user IP is used."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *   }
 * )
 *
 * @todo Add access callback information from Drupal 7.
 * @todo We should maybe use a dedicated data type for the ip address, as we
 * do in Drupal 7.
 */
class BanIp extends RulesBanActionBase {

  /**
   * Executes the BanIP action with the given context.
   *
   * @param string $ip
   *   (optional) The IP address that should be banned.
   */
  protected function doExecute($ip = NULL) {
    if (!isset($ip)) {
      $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    }

    $this->banManager->banIp($ip);
    $this->logger->notice('Banned IP address %ip', ['%ip' => $ip]);
  }

}
