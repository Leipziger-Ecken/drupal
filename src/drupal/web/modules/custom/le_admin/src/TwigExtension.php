<?php

namespace Drupal\le_admin;

use Drupal\views\Views;

/**
 * Twig extension with some useful functions and filters.
 *
 * Dependencies are not injected for performance reason.
 */
class TwigExtension extends \Twig_Extension
{

  /**
   * {@inheritdoc}
   */
  public function getFunctions()
  {
    $context_options = ['needs_context' => TRUE];
    $all_options = ['needs_environment' => TRUE, 'needs_context' => TRUE];
    return [
      new \Twig_SimpleFunction('get_destination', [$this, 'getDestination']),
      new \Twig_SimpleFunction('get_args', [$this, 'getArgs']),
    ];
  }

  public function getFilters()
  {
    return [

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'le_admin';
  }

  public function getDestination()
  {
    return \Drupal::destination()->getAsArray()['destination'];
  }

  public function getArgs()
  {
    $request = \Drupal::request();
    $path = $request->getPathInfo();
    return explode('/', $path);
  }
}
