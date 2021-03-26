<?php

namespace Drupal\le_admin\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;

class BreadcrumbBuilder implements BreadcrumbBuilderInterface{
  /**
  * {@inheritdoc}
  */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    $parameters = $route_match->getParameters()->all();
    $node = isset($parameters['node']) ? $parameters['node'] : null;
    if ($node && is_string($node)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    }
    $node_type = $node ? $node->getType() : null;
    // dd($route_name);

    return (
      in_array($route_name, [
        'entity.user.canonical',
        'entity.user.edit_form',
        'entity.user.cancel_form',
        'le_admin.user_dashboard',
      ]) ||
      in_array($node_type, [
        'le_akteur',
        'le_event',
        'webbuilder',
        'webbuilder_page',
        'project',
        'sponsor',
        'partner',
      ])
    );
  }

  /**
  * {@inheritdoc}
  */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $parameters = $route_match->getParameters()->all();
    $route_name = $route_match->getRouteName();
    $node = isset($parameters['node']) ? $parameters['node'] : null;
    if ($node && is_string($node)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    }
    $node_type = $node ? $node->getType() : null;
    
    $breadcrumb->addLink(
      Link::createFromRoute(t('Overview'), 'le_admin.user_dashboard')
    );

    if (in_array($route_name, [
      'entity.user.canonical',
      'entity.user.edit_form',
      'entity.user.cancel_form',
    ])) {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('My account'),
          'entity.user.canonical', ['user' => \Drupal::currentUser()->id()]
        )
      );
    }

    if ($route_name === 'entity.user.edit_form') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Edit'),
          'entity.user.edit_form', ['user' => \Drupal::currentUser()->id()]
        )
      );
    }

    if ($route_name === 'entity.user.cancel_form') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Cancel account'),
          'entity.user.cancel_form', ['user' => \Drupal::currentUser()->id()]
        )
      );
    }

    if (strpos($route_name, 'le_admin.user_akteur') === 0) {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Actor: :label', [':label' => $node->getTitle()]),
          'le_admin.user_akteur', ['node' => $node->id() ]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_events') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Events'),
          'le_admin.user_akteur_events',
          ['node' => $node->id()]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_projects') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Projects'),
          'le_admin.user_akteur_projects',
          ['node' => $node->id()]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_blog_articles') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Blog Articles'),
          'le_admin.user_akteur_blog_articles',
          ['node' => $node->id()]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_partners') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Partners'),
          'le_admin.user_akteur_partners',
          ['node' => $node->id()]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_sponsors') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Sponsors'),
          'le_admin.user_akteur_sponsors',
          ['node' => $node->id()]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_webbuilder') {
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Website'), 'le_admin.user_akteur_webbuilder', ['node' => $node->id() ]
        )
      );
    }

    if ($route_name === 'le_admin.user_webbuilder_pages') {
      $webbuilder = $parameters['node'];
      $akteur = \Drupal::entityTypeManager()->getStorage('node')->load($webbuilder->og_audience[0]->target_id);

      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Actor: :label', [':label' => $akteur->getTitle()]),
          'le_admin.user_akteur',
          ['node' => $akteur->id()]
        )
      );

      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Website'), 'le_admin.user_akteur_webbuilder', ['node' => $akteur->id() ]
        )
      );
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Pages'), 'le_admin.user_webbuilder_pages', ['node' => $webbuilder->id() ]
        )
      );
    }

    return $breadcrumb;
  }

}
