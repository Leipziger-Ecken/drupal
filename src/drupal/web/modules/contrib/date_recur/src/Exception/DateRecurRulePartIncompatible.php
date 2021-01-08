<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Exception;

/**
 * Thrown when an incompatible part and frequency are combined.
 */
class DateRecurRulePartIncompatible extends \InvalidArgumentException {}
