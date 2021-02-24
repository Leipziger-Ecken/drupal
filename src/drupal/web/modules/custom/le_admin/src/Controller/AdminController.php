<?php

namespace Drupal\le_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\Core\Url;
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
   * Provides the akteur contents page.
   *
   * @param \Drupal\node\Entity\Node $node
   *
   * @return array
   *   A renderable array of the akteur contents page.
   */
  public function userAkteurContents($node)
  {
    return [
      '#theme' => 'le_admin__user_akteur_contents',
      '#node' => $node,
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
    ];
  }
}
