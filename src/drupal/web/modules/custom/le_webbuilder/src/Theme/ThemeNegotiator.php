<?php

namespace Drupal\le_webbuilder\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

class ThemeNegotiator implements ThemeNegotiatorInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context to determine whether the route is an admin one.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, AdminContext $admin_context) {
    $this->user = $user;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->adminContext = $admin_context;
  }
  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match)
  {
    return true;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match)
  {
    $route_name = $route_match->getRouteName();
    $parameters = $route_match->getParameters();
    $node = $parameters->get('node') ?? $parameters->get('node_preview') ?? null;
    // dd($route_name);

    if (
      $node &&
      in_array($route_name, ['entity.node.canonical', 'entity.node.preview']) &&
      in_array(
        $node->getType(),
        [
          'webbuilder', 'webbuilder_page', 'webbuilder_preset',
          'blog_article', 'project', 'partner', 'sponsor',
        ]
      )
    ) {
      return 'leipzigerEckenWebbuilder';
    } elseif (
      $this->entityTypeManager->hasHandler('user_role', 'storage') &&
      $this->user->hasPermission('view the administration theme') &&
      (
        $this->adminContext->isAdminRoute($route_match->getRouteObject()) ||
        strpos($route_name, 'view.le_verwaltete_akteure') === 0 ||
        strpos($route_name, 'view.le_verwaltete_events') === 0 ||
        strpos($route_name, 'view.webbuilders') === 0 ||
        strpos($route_name, 'view.projects') === 0 ||
        strpos($route_name, 'view.partners') === 0 ||
        strpos($route_name, 'view.sponsors') === 0 ||
        strpos($route_name, 'view.blog') === 0 ||
        strpos($route_name, 'entity.user.') === 0 ||
        in_array($route_name, [
          'le_admin.user_dashboard',
          'le_admin.user_akteur_contents',
          'le_admin.user_akteur_webbuilder',
          'le_admin.user_webbuilder_pages',
        ])
      )
    ) {
      return $this->configFactory->get('system.theme')->get('admin') ?: NULL;
    }

    return $this->configFactory->get('system.theme')->get('default') ?: NULL;
  }

}
