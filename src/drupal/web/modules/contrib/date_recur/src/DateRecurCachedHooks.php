<?php

declare(strict_types = 1);

namespace Drupal\date_recur;

use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;

/**
 * Defines hooks that run when caches need to be rebuilt.
 *
 * Less common run hooks.
 */
class DateRecurCachedHooks {

  /**
   * Implements hook_field_info_alter().
   *
   * @see \hook_field_info_alter()
   * @see \date_recur_field_info_alter()
   */
  public function fieldInfoAlter(array &$info): void {
    foreach ($info as &$definition) {
      $class = $definition['class'];
      // Is date_recur or a subclass.
      if (($class == DateRecurItem::class) || (new \ReflectionClass($class))->isSubclassOf(DateRecurItem::class)) {
        $definition[DateRecurOccurrences::IS_DATE_RECUR] = 'TRUE';
      }
    }
  }

  /**
   * Implements hook_theme().
   *
   * @see \hook_theme()
   * @see \date_recur_theme()
   */
  public function hookTheme(array $existing, string $type, string $theme, string $path): array {
    return [
      'date_recur_basic_widget' => [
        'render element' => 'element',
      ],
      'date_recur_settings_frequency_table' => [
        'render element' => 'element',
      ],
      'date_recur_basic_formatter' => [
        'variables' => [
          'date' => NULL,
          'interpretation' => NULL,
          'is_recurring' => FALSE,
          'occurrences' => [],
        ],
      ],
    ];
  }

}
