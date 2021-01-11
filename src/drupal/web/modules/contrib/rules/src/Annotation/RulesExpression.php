<?php

namespace Drupal\rules\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the RulesExpression annotation class.
 *
 * @Annotation
 */
class RulesExpression extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the rules plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The class name of the form for displaying/editing this expression.
   *
   * @var string
   */
  public $form_class;

}
