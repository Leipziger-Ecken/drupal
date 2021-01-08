<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading date recur interpreter plugins.
 */
class DateRecurInterpreterPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The ID of the date recur interpreter entity using this plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructs a new DateRecurInterpreterPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $id
   *   The ID of the date recur interpreter entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $id) {
    parent::__construct($manager, $instance_id, $configuration);
    $this->id = $id;
  }

}
