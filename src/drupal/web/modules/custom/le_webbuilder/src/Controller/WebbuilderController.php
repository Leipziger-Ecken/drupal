<?php namespace Drupal\le_webbuilder\Controller;

use Drupal\Core\Controller\ControllerBase;

class WebbuilderController extends ControllerBase
{
  /*
   * Renders a node in webbuilder context
  **/
  public function viewNode($akteur, $webbuilder, $node)
  {
    $build = [
      '#theme' => 'node__' . $node->getType() . '__in_webbuilder',
      '#view_mode' => 'full',
      '#node' => $node,
      '#title' => $node->getTitle(),
      '#variables' => [
        'akteur' => $akteur,
        'webbuilder' => $webbuilder,
      ],
    ];

    return $build;
  }

  /*
   * Renders a webbuilder
  **/
  public function viewWebbuilder($akteur, $webbuilder)
  {
    $build = [
      '#theme' => 'node__webbuilder',
      '#view_mode' => 'full',
      '#node' => $webbuilder,
      '#title' => $webbuilder->getTitle(),
      '#variables' => [
        'akteur' => $akteur,
        'webbuilder' => $webbuilder,
      ],
    ];
    return $build;
  }
}