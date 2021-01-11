<?php

namespace Drupal\rules\Form\Expression;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rules\Ui\RulesUiHandlerTrait;

/**
 * Form handler for action containers.
 */
abstract class ExpressionContainerFormBase implements ExpressionFormInterface {
  use StringTranslationTrait;
  use ExpressionFormTrait;
  use RulesUiHandlerTrait;

  /**
   * Helper function to extract context parameter names/values from the config.
   *
   * @param array $configuration
   *   Configuration entity as a configuration array.
   *
   * @return string
   *   String containing a summary of context parameter names and values.
   */
  protected function getParameterDescription(array $configuration) {
    $parameters = [];
    // 'context_mapping' is for context parameters set in data selector mode.
    // 'context_values' is for context parameters set in direct input mode.
    $context = [];
    if (isset($configuration['context_values']) && isset($configuration['context_mapping'])) {
      // @todo Remove this if() check on context_values and context_mapping when
      // https://www.drupal.org/project/rules/issues/3103808 is fixed.
      $context = $configuration['context_mapping'] + $configuration['context_values'];
    }
    foreach ($context as $key => $value) {
      if ($value === FALSE) {
        $value = 'FALSE';
      }
      elseif ($value === TRUE) {
        $value = 'TRUE';
      }
      elseif ($value === NULL) {
        $value = 'NULL';
      }
      elseif ($value === '') {
        $value = "''";
      }
      elseif (is_array($value)) {
        $value = '[' . implode(', ', $value) . ']';
      }
      // @todo Truncate $value if it's "too long", so as not to clutter UI.
      // Perhaps we can display the full value on hover.
      $parameters[] = $key . ': ' . $value;
    }

    // Build description string.
    if (empty($parameters)) {
      $description = $this->t('Parameters: <none>');
    }
    else {
      $description = $this->t('Parameters: @name-value', ['@name-value' => implode(', ', $parameters)]);
    }

    return $description;
  }

}
