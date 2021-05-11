<?php

namespace Drupal\le_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for System routes.
 */
class AdminController extends ControllerBase
{
  /**
   * Provides the user dashboard page.
   *
  * @return array
   *   A renderable array of the user dashboard page.
   */
  public function userDashboard()
  {
    return [
      '#theme' => 'le_admin__user_dashboard',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur page.
   */
  public function userAkteur($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur',
      '#node' => $node,
      '#title' => $node->getTitle(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur events page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur events page.
   */
  public function userAkteurEvents($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_events',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Events'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur projects page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur projects page.
   */
  public function userAkteurProjects($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_projects',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Projects'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur blog_articles page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur blog_articles page.
   */
  public function userAkteurBlogArticles($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_blog_articles',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Blog Articles'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur partners page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur partners page.
   */
  public function userAkteurPartners($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_partners',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Partners'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur sponsors page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur sponsors page.
   */
  public function userAkteurSponsors($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_sponsors',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Sponsors'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur webbuilder page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur webbuilder page.
   */
  public function userAkteurWebbuilder($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_webbuilder',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Website'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the akteur add webbuilder page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur add webbuilder page.
   */
  public function userAkteurAddWebbuilder($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_add_webbuilder',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Website'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Provides the webbuilder pages page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the webbuilder pages page.
   */
  public function userWebbuilderPages($node)
  {
    return [
      '#theme' => 'le_admin__user_webbuilder_pages',
      '#node' => $node,
      '#title' => $node->getTitle() . ': ' . t('Website pages'),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Publishes a webbuilder
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return Redirect
   */
  public function userWebbuilderPublish($node)
  {
    $node->status = 1;
    $node->save();
    $destination = \Drupal::request()->get('destination');
    drupal_set_message(t('Published the website.'), 'status', true);
    return new RedirectResponse($destination);
  }

  /**
   * Unpublishes a webbuilder
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return Redirect
   */
  public function userWebbuilderUnpublish($node)
  {
    $node->status = 0;
    $node->save();
    $destination = \Drupal::request()->get('destination');
    drupal_set_message(t('Unpublished the website.'), 'status', true);
    return new RedirectResponse($destination);
  }

  /**
   * Creates a new webbuilder from a preset
   */
  public function createWebbuilderFromPreset($webbuilder_preset)
  {
    $user_id = \Drupal::currentUser()->id();
    $akteur_id = \Drupal::request()->get('le_akteur') ?? $webbuilder_preset->og_audience->target_id;
    $akteur = \Drupal::entityTypeManager()->getStorage('node')->load($akteur_id);
    // deep clone the webbuilder
    $webbuilder = $webbuilder_preset->createDuplicate();
    $webbuilder_preset_id = $webbuilder_preset->id();

    // set current user as author and unpublish it
    $webbuilder->uid = $user_id;
    $webbuilder->status = 0;
    $webbuilder->published_at = null;
    $webbuilder->set('field_is_preset', false);

    if ($akteur) {
      $webbuilder->title = $akteur->title;
    }

    // set the og_audience if given
    if ($akteur_id) {
      $webbuilder->set('og_audience', $akteur_id);
    }

    // remember the original index pages
    $frontpage_id = null;
    $preset_frontpage_id = null;
    $blog_page_id = null;
    $preset_blog_page_id = null;
    $events_page_id = null;
    $preset_events_page_id = null;
    $projects_page_id = null;
    $preset_projects_page_id = null;

    if ($webbuilder_preset->field_frontpage[0]) {
      $preset_frontpage_id = $webbuilder_preset->field_frontpage[0]->target_id;
    };
    if ($webbuilder_preset->field_blog_page[0]) {
      $preset_blog_page_id = $webbuilder_preset->field_blog_page[0]->target_id;
    };
    if ($webbuilder_preset->field_events_page[0]) {
      $preset_events_page_id = $webbuilder_preset->field_events_page[0]->target_id;
    };
    if ($webbuilder_preset->field_projects_page[0]) {
      $preset_projects_page_id = $webbuilder_preset->field_projects_page[0]->target_id;
    };

    // now save the webbuilder
    $webbuilder->save();
    $webbuilder_id = $webbuilder->id();

    // load preset pages
    $pages_query = \Drupal::entityQuery('node');
    $pages_query->condition('type', 'webbuilder_page');
    $pages_query->condition('field_webbuilder', $webbuilder_preset_id);
    $result = $pages_query->execute();
    $preset_pages = [];
    foreach ($result as $nid) {
      $preset_pages[$nid] = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    }

    // now clone the pages and the paragraphs
    $pages_to_cloned_pages = [];
    $parent_pages = [];

    foreach ($preset_pages as $nid => $page) {
      $cloned_page = $page->createDuplicate();
      $cloned_page->set('field_webbuilder', $webbuilder_id);
      $cloned_page->set('og_audience', $akteur_id);
      $cloned_page->uid = $user_id;

      // clone paragraphs
      foreach ($cloned_page->field_contents as $paragraph) {
        $paragraph->entity = $paragraph->entity->createDuplicate();
        $paragraph->entity->uid = $user_id;
      }

      $cloned_page->save();

      $parent_page_id = isset($page->field_parent[0]) ? $page->field_parent[0]->target_id : null;
      $pages_to_cloned_pages[$page->id()] = $cloned_page;

      if ($parent_page_id) {
        $parent_pages[$page->id()] = $parent_page_id;
      }

      // if the original page an index page,
      // use the ID of the cloned page and set it as the index page
      // for the new webbuilder
      // we have to do non strict equality here, as IDs can be ints or strings
      if ($nid == $preset_frontpage_id) {
        $frontpage_id = $cloned_page->id();
      }
      if ($nid == $preset_blog_page_id) {
        $blog_page_id = $cloned_page->id();
      }
      if ($nid == $preset_events_page_id) {
        $events_page_id = $cloned_page->id();
      }
      if ($nid == $preset_projects_page_id) {
        $projects_page_id = $cloned_page->id();
      }
    }

    // now update the parent ids using the lookups
    foreach ($parent_pages as $page_id => $parent_page_id) {
      if (isset($pages_to_cloned_pages[$parent_page_id]) && isset($pages_to_cloned_pages[$page_id])) {
        $cloned_parent_page_id = $pages_to_cloned_pages[$parent_page_id]->id();
        $cloned_page = $pages_to_cloned_pages[$page_id];
        $cloned_page->set('field_parent', $cloned_parent_page_id);
        $cloned_page->save();
      }
    }

    // set the new index page ids
    if ($frontpage_id || $blog_page_id || $events_page_id || $projects_page_id) {
      $webbuilder->set('field_frontpage', $frontpage_id);
      $webbuilder->set('field_blog_page', $blog_page_id);
      $webbuilder->set('field_events_page', $events_page_id);
      $webbuilder->set('field_projects_page', $projects_page_id);

      // save again, to store new index pages
      $webbuilder->save();
    }

    return $this->redirect('entity.node.edit_form', [
      'node' => $webbuilder->id(),
    ]);
  }
}
