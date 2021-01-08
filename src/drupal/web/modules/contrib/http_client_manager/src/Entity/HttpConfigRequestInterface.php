<?php

namespace Drupal\http_client_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Http Config Request entities.
 */
interface HttpConfigRequestInterface extends ConfigEntityInterface {

  /**
   * Executes configured Http Request.
   *
   * @return mixed
   *   The command response.
   */
  public function execute();

  /**
   * Get configured command parameters.
   *
   * @return array
   *   An array of parameters.
   */
  public function getParameters();

}
