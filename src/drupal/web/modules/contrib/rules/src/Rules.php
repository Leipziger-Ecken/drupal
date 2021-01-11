<?php

namespace Drupal\rules;

/**
 * Class containing shortcuts for procedural code.
 *
 * This helpers should only be used in situations where dependencies cannot be
 * injected; e.g., in hook implementations or static methods.
 *
 * @see \Drupal
 */
class Rules {

  /**
   * The current configuration schema version.
   */
  const CONFIG_VERSION = 3.0;

}
