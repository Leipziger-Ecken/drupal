<?php

/**
 * @file
 * Rules module integration with Drush 8 and earlier.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Implements hook_drush_command().
 */
function rules_drush_command() {
  $items = [];
  $items['rules-list'] = [
    'aliases' => ['rules'],
    'description' => dt('Lists all the active and inactive rules for your site.'),
    'drupal dependencies' => ['rules'],
    'core' => ['8+'],
    'options' => [
      'type' => [
        'description' => dt('Restricts output to either "rule" or "component". Default is to list both.'),
        'example-value' => 'rule, component',
      ],
    ],
    'examples' => [
      'drush rules-list' => dt('Lists both Reaction Rules and Rules Components.'),
      'drush rules-list --type=component' => dt('Lists only Rules Components.'),
      'drush rules-list --fields=rule' => dt('Lists just the machine names.'),
      'drush rules-list --pipe' => dt('Outputs machine names in a format suitable for piping.'),
    ],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'rule' => (string) dt('Rule'),
        'label' => (string) dt('Label'),
        'event' => (string) dt('Event'),
        'active' => (string) dt('Active'),
        /* 'status' => (string) dt('Status'), */
      ],
      'output-data-type' => 'format-table',
    ],
  ];
  $items['rules-enable'] = [
    'aliases' => ['re'],
    'description' => dt('Enables a rule on your site.'),
    'arguments' => [
      'rule' => dt('Reaction Rule name (machine id) to enable.'),
    ],
    'required-arguments' => TRUE,
    'drupal dependencies' => ['rules'],
    'core' => ['8+'],
    'examples' => [
      'drush rules-enable test_rule' => dt('Enables the rule with machine name "test_rule".'),
    ],
  ];
  $items['rules-disable'] = [
    'aliases' => ['rd'],
    'description' => dt('Disables a rule on your site.'),
    'arguments' => [
      'rule' => dt('Reaction Rule name (machine id) to disable.'),
    ],
    'required-arguments' => TRUE,
    'drupal dependencies' => ['rules'],
    'core' => ['8+'],
    'examples' => [
      'drush rules-disable test_rule' => dt('Disables the rule with machine name "test_rule".'),
    ],
  ];
  $items['rules-delete'] = [
    'description' => dt('Deletes a rule on your site.'),
    'arguments' => [
      'rule' => dt('Rule name (machine id) to delete.'),
    ],
    'required-arguments' => TRUE,
    'drupal dependencies' => ['rules'],
    'core' => ['8+'],
    'examples' => [
      'drush rules-delete test_rule' => dt('Permanently deletes the rule with machine name "test_rule".'),
    ],
  ];
  $items['rules-export'] = [
    'description' => dt('Exports a Rule in YAML format.'),
    'arguments' => [
      'rule' => dt('Rule name (machine id) to export.'),
    ],
    'required-arguments' => TRUE,
    'examples' => [
      'drush rules-export test_rule > rules.reaction.test_rule.yml' => dt('Exports the Rule with machine name "test_rule" and saves it in a .yml file.'),
      'drush --pipe --type=component rules-list | xargs -I{}  sh -c "drush rules-export \'{}\' > \'rules.component.{}.yml\'"' => dt('Exports all Rules Components into individual YAML files.'),
    ],
    'drupal dependencies' => ['rules'],
    'core' => ['8+'],
  ];
  /*
   * @codingStandardsIgnoreStart
   *
   * @todo Not yet implemented.
   *
   * $items['rules-revert'] = [
   *   'description' => dt('Reverts a rule to its original state on your site.'),
   *   'arguments' => [
   *     'rule' => dt('Rule name (machine id) to revert.'),
   *   ],
   *   'required-arguments' => TRUE,
   *   'drupal dependencies' => ['rules'],
   *   'core' => ['8+'],
   *   'examples' => [
   *     'drush rules-revert test_rule' => dt('Restores a module-provided Rule to its original state. If the Rule hasn\'t been customized on the site, this has no effect.'),
   *   ],
   * ];
   * @codingStandardsIgnoreEnd
   */

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function rules_drush_help($section) {
  switch ($section) {
    case 'meta:rules:title':
      return dt('Rules commands');

    case 'meta:rules:summary':
      return dt('Developer-specific commands for the Rules module.');

    case 'drush:rules-list':
      return dt('Lists all the rules on your site.');

    case 'drush:rules-enable':
      return dt('Enables/activates a Reaction Rule on your site.');

    case 'drush:rules-disable':
      return dt('Disables/deactivates a Reaction Rule on your site.');

    /*
     * @codingStandardsIgnoreStart
     *
     * @todo Not yet implemented.
     *
     * case 'drush:rules-revert':
     *   return dt('Reverts a module-provided rule to its original state on your site.');
     * @codingStandardsIgnoreEnd
     */

    case 'drush:rules-delete':
      return dt('Deletes a rule on your site.');

    case 'drush:rules-export':
      return dt('Exports a rule.');
  }
}

/**
 * Gets a list of all rules.
 */
function drush_rules_list() {
  // Type is 'rule', or 'component'. Any other value (or no value) will
  // list both Reaction Rules and Rules Components.
  switch (drush_get_option('type')) {
    case 'rule':
      $types = ['reaction'];
      break;

    case 'component':
      $types = ['component'];
      break;

    default:
      $types = ['reaction', 'component'];
      break;
  }

  // Loop over type option.
  $rows = [];
  foreach ($types as $type) {
    $rules = \Drupal::configFactory()->listAll('rules.' . $type);
    $event_manager = \Drupal::service('plugin.manager.rules_event');

    // Loop over configuration entities for this $type.
    foreach ($rules as $config) {
      $rule = \Drupal::configFactory()->get($config);
      if (!empty($rule->get('id')) && !empty($rule->get('label'))) {
        $events = [];
        $active = '';
        // Components don't have events and can't be enabled/disabled.
        if ($type == 'reaction') {
          foreach ($rule->get('events') as $event) {
            $plugin = $event_manager->getDefinition($event['event_name']);
            $events[] = (string) $plugin['label'];
          }
          $active = $rule->get('status') ? dt('Enabled') : dt('Disabled');
        }
        $rows[(string) $rule->get('id')] = [
          'rule' => (string) $rule->get('id'),
          'label' => (string) $rule->get('label'),
          'event' => implode(', ', $events),
          'active' => (string) $active,
          /*
           * 'status' => what goes here ?
           *
           * @todo Need to figure out how to determine the value for the
           * 'status' column. That is, whether the rule is defined by a module
           * ('Default'), or is defined by a module then modified in a
           * site-specific way ('Overridden'), or is a custom rule built for
           * this site ('Custom').
           * Maybe $rule->has Overrides() tells us if this is Custom?
           */
        ];
      }
    }
  }

  return $rows;
}

/**
 * Enables a Reaction Rule on the site.
 */
function drush_rules_enable($rule) {
  // The $rule argument must be a Reaction Rule.
  if (\Drupal::service('config.storage')->exists('rules.reaction.' . $rule)) {
    $config = \Drupal::configFactory()->getEditable('rules.reaction.' . $rule);
  }
  else {
    return drush_set_error('', dt('Could not find a Reaction Rule named "@name".', ['@name' => $rule]));
  }

  if (!$config->get('status')) {
    $config->set('status', TRUE);
    $config->save();
    drush_log(dt('The rule "@name" has been enabled.', ['@name' => $rule]), 'success');
  }
  else {
    drush_log(dt('The rule "@name" is already enabled.', ['@name' => $rule]), 'warning');
  }
}

/**
 * Disables a Reaction Rule on the site.
 */
function drush_rules_disable($rule) {
  // The $rule argument must be a Reaction Rule.
  if (\Drupal::service('config.storage')->exists('rules.reaction.' . $rule)) {
    $config = \Drupal::configFactory()->getEditable('rules.reaction.' . $rule);
  }
  else {
    return drush_set_error('', dt('Could not find a Reaction Rule named "@name".', ['@name' => $rule]));
  }

  if ($config->get('status')) {
    $config->set('status', FALSE);
    $config->save();
    drush_log(dt('The rule "@name" has been disabled.', ['@name' => $rule]), 'success');
  }
  else {
    drush_log(dt('The rule "@name" is already disabled.', ['@name' => $rule]), 'warning');
  }
}

/**
 * Deletes a rule on the site.
 */
function drush_rules_delete($rule) {
  // The $rule argument could refer to a Reaction Rule or a Rules Component.
  if (\Drupal::service('config.storage')->exists('rules.reaction.' . $rule)) {
    $config = \Drupal::configFactory()->getEditable('rules.reaction.' . $rule);
  }
  elseif (\Drupal::service('config.storage')->exists('rules.component.' . $rule)) {
    $config = \Drupal::configFactory()->getEditable('rules.component.' . $rule);
  }
  else {
    return drush_set_error('', dt('Could not find a Reaction Rule or a Rules Component named "@name".', ['@name' => $rule]));
  }

  if (drush_confirm(dt('Are you sure you want to delete the rule named "@name"? This action cannot be undone.', ['@name' => $rule]))) {
    $config->delete();
    drush_log(dt('The rule "@name" has been deleted.', ['@name' => $rule]), 'success');
  }
  else {
    drush_user_abort();
  }
}

/**
 * Exports a single rule.
 */
function drush_rules_export($rule) {
  // The $rule argument could refer to a Reaction Rule or a Rules Component.
  $config = \Drupal::service('config.storage')->read('rules.reaction.' . $rule);
  if (empty($config)) {
    $config = \Drupal::service('config.storage')->read('rules.component.' . $rule);
    if (empty($config)) {
      return drush_set_error('', dt('Could not find a Reaction Rule or a Rules Component named "@name".', ['@name' => $rule]));
    }
  }

  drush_print(Yaml::encode($config), 0, NULL, FALSE);
  drush_log(dt('The rule "@name" has been exported.', ['@name' => $rule]), 'success');
}
