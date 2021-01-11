<?php

namespace Drupal\rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\InvalidArgumentException;

/**
 * Provides an action to convert data from one type to another.
 *
 * @RulesAction(
 *   id = "rules_data_convert",
 *   label = @Translation("Convert data"),
 *   category = @Translation("Data"),
 *   context_definitions = {
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Value"),
 *       description = @Translation("The first input value for the calculation."),
 *       assignment_restriction = "selector"
 *     ),
 *     "target_type" = @ContextDefinition("string",
 *       label = @Translation("Target type"),
 *       description = @Translation("The data type to convert a value to."),
 *       assignment_restriction = "input"
 *     ),
 *     "rounding_behavior" = @ContextDefinition("string",
 *       label = @Translation("Rounding behavior"),
 *       description = @Translation("For integer target types, specify how the conversion result should be rounded."),
 *       default_value = NULL,
 *       required = FALSE
 *     ),
 *   },
 *   provides = {
 *     "conversion_result" = @ContextDefinition("any",
 *       label = @Translation("Conversion result")
 *     ),
 *   }
 * )
 * @todo Add rounding_behaviour default value "round".
 * @todo Add options_list for target type.
 * @todo Specify the right data type for the provided result.
 */
class DataConvert extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
    if ($type = $this->getContextValue('target_type')) {
      $this->pluginDefinition['provides']['conversion_result']->setDataType($type);
    }
  }

  /**
   * Executes the plugin.
   *
   * @param mixed $value
   *   The input value.
   * @param string $target_type
   *   The target type the value should be converted into.
   * @param string $rounding_behavior
   *   The behaviour for rounding.
   */
  protected function doExecute($value, $target_type, $rounding_behavior = NULL) {
    // @todo Add support for objects implementing __toString().
    if (!is_scalar($value)) {
      throw new InvalidArgumentException('Only scalar values are supported.');
    }

    // Ensure valid contexts have been provided.
    if (isset($rounding_behavior) && $target_type != 'integer') {
      throw new InvalidArgumentException('A rounding behavior only makes sense with an integer target type.');
    }

    // First apply the rounding behavior if given.
    if (!empty($rounding_behavior)) {
      switch ($rounding_behavior) {
        case 'up':
          $value = ceil($value);
          break;

        case 'down':
          $value = floor($value);
          break;

        case 'round':
          $value = round($value);
          break;

        default:
          throw new InvalidArgumentException("Unknown rounding behavior: $rounding_behavior");
      }
    }

    switch ($target_type) {
      case 'float':
        $result = floatval($value);
        break;

      case 'integer':
        $result = intval($value);
        break;

      case 'string':
        $result = strval($value);
        break;

      default:
        throw new InvalidArgumentException("Unknown target type: $target_type");
    }

    $this->setProvidedValue('conversion_result', $result);
  }

}
