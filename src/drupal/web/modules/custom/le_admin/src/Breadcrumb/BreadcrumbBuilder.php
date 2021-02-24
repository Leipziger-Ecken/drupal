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
    $node_type = isset($parameters['node']) ? $parameters['node']->getType() : null;

    return (
      in_array($route_name, [
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
    $node_type = isset($parameters['node']) ? $parameters['node']->getType() : null;

    $breadcrumb->addLink(
      Link::createFromRoute(t('Übersicht'), 'le_admin.user_dashboard')
    );

    if ($route_name === 'le_admin.user_akteur_contents') {
      $node = $parameters['node'];
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Akteur: :label', [':label' => $node->title[0]->value]),
          'le_admin.user_akteur_contents', ['node' => $node->id() ]
        )
      );
    }

    if ($route_name === 'le_admin.user_akteur_webbuilder') {
      $node = $parameters['node'];
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Akteur: :label', [':label' => $node->title[0]->value]),
          'le_admin.user_akteur_contents', ['node' => $node->id() ]
        )
      );
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Webbaukasten'), 'le_admin.user_akteur_webbuilder', ['node' => $node->id() ]
        )
      );
    }

    if ($route_name === 'le_admin.user_webbuilder_pages') {
      $webbuilder = $parameters['node'];
      $akteur = \Drupal::entityManager()->getStorage('node')->load($webbuilder->og_audience[0]->target_id);
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Akteur: :label', [':label' => $akteur->title[0]->value]),
          'le_admin.user_akteur_contents', ['node' => $node->id() ]
        )
      );
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Webbaukasten'), 'le_admin.user_akteur_webbuilder', ['node' => $akteur->id() ]
        )
      );
      $breadcrumb->addLink(
        Link::createFromRoute(
          t('Seiten'), 'le_admin.user_webbuilder_pages', ['node' => $webbuilder->id() ]
        )
      );
    }

    return $breadcrumb;
  }

}