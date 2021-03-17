<?php

namespace Drupal\le_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for System routes.
 */
class AdminController extends ControllerBase
{
  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Constructs a new SystemController.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   */
  public function __construct(FormBuilderInterface $form_builder, MenuLinkTreeInterface $menu_link_tree)
  {
    $this->formBuilder = $form_builder;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('menu.link_tree')
    );
  }

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
