<?php

namespace Drupal\le_admin\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class AdminThemeNegotiator implements ThemeNegotiatorInterface {

  public function __construct(
    $currentUser, $configFactory, $entityManager, $adminContext
  ) {
    $this->user = $currentUser;
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
    $routeName = $route_match->getRouteName();
    // dd($routeName);
    return (
      strpos($routeName, 'system.') === 0
    );
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
    if (in_array('authenticated', $this->user->getRoles())) {
      return 'gin';
    }

    return null;
  }
}
