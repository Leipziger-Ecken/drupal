<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Interface for date recur interpreter plugins.
 */
interface DateRecurInterpreterPluginInterface extends ConfigurablePluginInterface, PluginWithFormsInterface {

  /**
   * Interpret a set of rules in a language.
   *
   * @param \Drupal\date_recur\DateRecurRuleInterface[] $rules
   *   The rules.
   * @param string $language
   *   The two-letter language code.
   * @param \DateTimeZone|null $timeZone
   *   Time zone to display dates in. Time zone from rules is not used because
   *   time zone used to calculate recurring dates may be different. Use null
   *   for default PHP time zone.
   *
   * @return string
   *   Rules interpreted into a string.
   */
  public function interpret(array $rules, string $language, ?\DateTimeZone $timeZone = NULL): string;

  /**
   * The languages supported by this plugin.
   *
   * Two letter langcodes. E.g: 'en', 'fr', etc.
   *
   * @return string[]
   *   Supported languages.
   */
  public function supportedLanguages(): array;

}
