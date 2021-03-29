<?php namespace Drupal\le_webbuilder\Controller;

use Drupal\node\Controller\NodeViewController;

class WebbuilderController extends NodeViewController
{
  /*
   * Renders a node in webbuilder context
  **/
  public function viewNode($akteur, $webbuilder, $node)
  {
    $build = parent::view($node, 'in_webbuilder');
    $build['#title'] = $webbuilder->getTitle();
    $build['#aktuer'] = $akteur;
    $build['#webbuilder'] = $webbuilder;

    return $build;
  }
}