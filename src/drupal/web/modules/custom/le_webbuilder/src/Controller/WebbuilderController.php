<?php namespace Drupal\le_webbuilder\Controller;

use Drupal\Core\Controller\ControllerBase;

class WebbuilderController extends ControllerBase
{
  /*
   * Renders a node in webbuilder context
  **/
  public function viewNode($akteur, $webbuilder, $node)
  {
    $node->setViewMode('webbuilder');
    $build = [
      '#type' => 'page',
      'node' => $node,
      'akteur' => $akteur,
      'webbuilder' => $webbuilder,
    ];
    return $build;
  }

  /*
   * Renders a webbuilder
  **/
  public function viewWebbuilder($akteur, $webbuilder)
  {
    $build = [
      '#type' => 'page',
      'node' => $webbuilder,
      'akteur' => $akteur,
      'webbuilder' => $webbuilder,
    ];
    return $build;
  }

  /*
   * Renders a webbuilder page
  **/
  public function viewWebbuilderPage($akteur, $webbuilder, $node)
  {
    $build = [
      '#type' => 'page',
      'node' => $node,
      'akteur' => $akteur,
      'webbuilder' => $webbuilder,
    ];
    return $build;
  }
}