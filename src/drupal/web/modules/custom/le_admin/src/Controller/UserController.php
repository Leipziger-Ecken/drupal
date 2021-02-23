<?php

namespace Drupal\le_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for System routes.
 */
class UserController extends ControllerBase
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
   * Constructs a new UserController.
   *
   */
  public function __construct()
  {

  }

  /**
   * Provide the user dashboard page.
   *
  * @return array
   *   A renderable array of the administration overview page.
   */
  public function dashboard()
  {
    return [];
  }

}
