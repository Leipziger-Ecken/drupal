<?php

namespace Drupal\le_admin\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for System routes.
 */
class ApiController extends ControllerBase
{
  public function listWebbuilderPageTree($webbuilder)
  {
    $destination = \Drupal::request()->get('destination');
    $pages = [];
    $page_tree = [];
    $pages_query = \Drupal::entityQuery('node');
    $pages_query->condition('type', 'webbuilder_page');
    $pages_query->condition('field_webbuilder', $webbuilder->id());
    $result = $pages_query->execute();
    foreach ($result as $nid) {
      $pages[$nid] = \Drupal::entityManager()->getStorage('node')->load($nid);
    }
    
    function _collectPages($pages, $destination, $parent_id = null) {
      $_pages = [];

      foreach ($pages as $nid => $page) {
        $parent = $page->get('field_parent');
        $page_parent_id = $parent && isset($parent[0]) && isset($parent[0]->target_id) ? $parent[0]->target_id : null;
        
        if ($parent_id != $page_parent_id) {
          continue;
        }

        $_pages[] = [
          'nid' => $nid,
          'edit_url' => Url::fromUserInput('/node/' . $nid . '/edit', ['query' => ['destination' => $destination]])->toString(),
          'preview_url' => Url::fromUserInput('/node/' . $nid, ['query' => ['preview' => 1]])->toString(),
          'title' => $page->getTitle(),
          'status' => intval($page->status[0]->value),
          'children' => _collectPages($pages, $destination, $nid),
        ];
      }

      return $_pages;
    }

    $page_tree = _collectPages($pages, $destination);

    return new JsonResponse($page_tree);
  }

  public function sortWebbuilderPage($webbuilder, $page)
  {
    $parent_id = \Drupal::request()->get('parent_id');
    $sibling_id = \Drupal::request()->get('sibling_id');
  }

  public function getWebbuilderFrontpage($webbuilder)
  {

  }

  public function setWebbuilderFrontPage($webbuilder)
  {
    $page_id = \Drupal::request()->get('page_id');
  }
}
