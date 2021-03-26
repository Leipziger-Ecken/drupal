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
}
