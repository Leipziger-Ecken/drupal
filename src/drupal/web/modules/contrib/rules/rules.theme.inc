<?php

/**
 * @file
 * Rules theme preprocessor function to prepare variables for use in templates.
 */

/**
 * Prepares variables for rules debug log element templates.
 *
 * Default template: rules-debug-log-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 */
function template_preprocess_rules_debug_log_element(array &$variables) {
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += [
    '#attributes' => [],
  ];

  // Here we just prepare the variables we send to the template.
  $formatted_diff = round(($element['#timestamp'] - $element['#starttime']) * 1000, 3) . ' ms';
  $variables['time'] = $formatted_diff;
  $variables['level'] = $element['#level'];
  $variables['text'] = $element['#text'];
  if (isset($element['#link'])) {
    $variables['link'] = [
      '#type' => 'link',
      '#title' => $element['#link']['title'],
      '#url' => $element['#link']['url'],
    ];
  }
  $variables['attributes'] = $element['#attributes'];
}
