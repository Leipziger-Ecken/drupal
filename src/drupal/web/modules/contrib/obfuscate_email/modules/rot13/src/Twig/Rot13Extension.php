<?php

namespace Drupal\rot13\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

/**
 * A class providing Drupal Twig extensions.
 *
 * Specifically Twig functions, filter and node visitors.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class Rot13Extension extends Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new Twig_SimpleFilter('rot13', 'str_rot13'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'rot13_twig_extension';
  }

}
