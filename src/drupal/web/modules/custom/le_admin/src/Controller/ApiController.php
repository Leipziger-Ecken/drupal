<?php

namespace Drupal\le_admin\Controller;

use Drupal\Component\Serialization\Json;
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
    $pages_query->sort('field_weight', 'asc');
    $result = $pages_query->execute();
    
    foreach ($result as $nid) {
      $pages[$nid] = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    }
    
    function _collectPages($pages, $destination, $parent_id = null) {
      $_pages = [];

      foreach ($pages as $nid => $page) {
        $parent = $page->get('field_parent');
        $page_parent_id = intval($parent && isset($parent[0]) && isset($parent[0]->target_id) ? $parent[0]->target_id : null);
        
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
    $data = json_decode(\Drupal::request()->getContent(), true);
    $parent_id = $data['parent_id'] ?? null;
    $sibling_id = $data['sibling_id'] ?? null;

    try {
      self::sortPage($page, $parent_id, $sibling_id);
    } catch (\Exception $err) {
      return new JsonResponse(['error' => $err->getMessage()], 400);
    }

    return new JsonResponse(['success' => true]);
  }

  public static function sortPage($page, $parent_id, $sibling_id)
  {
    if ($parent_id == $page->id()) {
      throw new \Exception('Cannot make page parent of itself.');
    }
    if ($sibling_id == $page->id()) {
      throw new \Exception('Cannot make page sibling of itself.');
    }

    $children_query = \Drupal::entityQuery('node');
    $children_query->condition('type', 'webbuilder_page');
    $children_query->condition('field_parent', $page->id());
    $child_ids = array_values($children_query->execute());

    if (in_array($parent_id, $child_ids)) {
      throw new \Exception('Cannot make page parent of its child.');
    }

    $new_weight = 0;

    $page->save();

    // normalize weights of pages on same level
    $pages_query = \Drupal::entityQuery('node');
    $pages_query->condition('type', 'webbuilder_page');
    if ($parent_id) {
      $pages_query->condition('field_parent', $parent_id);
    } else {
      $pages_query->notExists('field_parent');
    }
    $pages_query->condition('nid', $page->id(), '!=');
    $pages_query->sort('field_weight', 'asc');
    $page_ids = $pages_query->execute();

    $weight = $sibling_id ? 0 : 1;

    foreach ($page_ids as $nid) {
      $_page = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

      $_page->set('field_weight', $weight);
      $_page->save();

      if ($nid == $sibling_id) {
        $new_weight = $weight;
        $weight++;
      }

      $weight++;
    }

    // update page weight and parent
    $page->set('field_parent', $parent_id);
    $page->set('field_weight', $new_weight);
    $page->save();
  }

  protected function _setWebbuilderPage($field, $webbuilder)
  {
    $data = json_decode(\Drupal::request()->getContent(), true);
    $page_id = $data['page_id'] ?? null;

    if (!$page_id) {
      return new JsonResponse(['error' => 'Missing page_id'], 400);
    }

    $webbuilder->set($field, $page_id);
    $webbuilder->save();

    return new JsonResponse(['success' => true]);
  }

  public function setWebbuilderFrontPage($webbuilder)
  {
    return $this->_setWebbuilderPage('field_frontpage', $webbuilder);
  }

  public function setWebbuilderBlogPage($webbuilder)
  {
    return $this->_setWebbuilderPage('field_blog_page', $webbuilder);
  }

  public function setWebbuilderEventsPage($webbuilder)
  {
    return $this->_setWebbuilderPage('field_events_page', $webbuilder);
  }

  public function setWebbuilderProjectsPage($webbuilder)
  {
    return $this->_setWebbuilderPage('field_projects_page', $webbuilder);
  }
}
