<?php

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\block\Entity\Block;

const le_admin_link_button_classes = [
  'py-2', 'px-4', 'mr-4', 'border', 'border-gray-300', 'hover:bg-gray-100', 'hover:border-gray-400', 'rounded-md',
];

/**
 * Implements hook_page_attachments_alter
 * 
 */
function le_admin_page_attachments_alter(array &$attachments)
{
  $user = \Drupal::currentUser();
  $roles = $user->getRoles();

  if (in_array('authenticated', $roles)) {
    $attachments['#attached']['library'][] = 'le_admin/admin';
  }
}

/**
 * Implements hook_theme
 */
function le_admin_theme($existing, $type, $theme, $path)
{
  return [
    'le_admin__user_dashboard' => [
      'variables' => [],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_events' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_projects' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_blog_articles' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_partners' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_sponsors' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_akteur_webbuilder' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'le_admin__user_webbuilder_pages' => [
      'variables' => [
        'node' => null,
      ],
      'render element' => 'elements',
    ],
    'node__le_akteur__backend_teaser' => [
      'template' => 'node/node--le-akteur--backend-teaser',
      'base hook' => 'node',
    ],
    'node__le_event__backend_teaser' => [
      'template' => 'node/node--le-event--backend-teaser',
      'base hook' => 'node',
    ],
    'node__project__backend_teaser' => [
      'template' => 'node/node--project--backend-teaser',
      'base hook' => 'node',
    ],
    'node__blog_article__backend_teaser' => [
      'template' => 'node/node--blog-article--backend-teaser',
      'base hook' => 'node',
    ],
    'node__partner__backend_teaser' => [
      'template' => 'node/node--partner--backend-teaser',
      'base hook' => 'node',
    ],
    'node__sponsor__backend_teaser' => [
      'template' => 'node/node--sponsor--backend-teaser',
      'base hook' => 'node',
    ],
    'node__webbuilder__backend_teaser' => [
      'template' => 'node/node--webbuilder--backend-teaser',
      'base hook' => 'node',
    ],
    'node__webbuilder__webbuilder_preset_teaser' => [
      'template' => 'node/node--webbuilder--webbuilder-preset-teaser',
      'base hook' => 'node',
    ],
    'node__webbuilder_page__backend_teaser' => [
      'template' => 'node/node--webbuilder-page--backend-teaser',
      'base hook' => 'node',
    ],
    'views_view__le_akteur_has_events__dashboard' => [
      'template' => 'views/views-view--le-akteur-has-events--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__projects__dashboard' => [
      'template' => 'views/views-view--projects--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__blog__dashboard' => [
      'template' => 'views/views-view--blog--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__sponsors__dashboard' => [
      'template' => 'views/views-view--sponsors--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__partners__dashboard' => [
      'template' => 'views/views-view--partners--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__webbuilders__dashboard' => [
      'template' => 'views/views-view--webbuilders--dashboard',
      'base hook' => 'views_view',
    ],
    'views_view__webbuilders__dashboard_presets' => [
      'template' => 'views/views-view--webbuilders--dashboard-presets',
      'base hook' => 'views_view',
    ],
  ];
}

/**
 * Implements hook_block_access
 *
 */
function le_admin_block_access(Block $block, $operation, AccountInterface $account)
{
  // hide primary and secondary tasks for non-admin users
  if (
    $operation !== 'view' ||
    !in_array($block->id(), [
      'gin_primary_local_tasks',
      'gin_secondary_local_tasks',
    ])
  ) {
    return AccessResult::neutral();
  }

  $user = \Drupal::currentUser();
  $roles = $user->getRoles();
  $allowed_roles = [
    'administrator', ' le_role_redakteur',
  ];

  foreach ($allowed_roles as $allowed_role) {
    if (in_array($allowed_role, $roles)) {
      return AccessResult::neutral();
    }
  }

  return AccessResult::forbiddenIf(true)->addCacheableDependency($block);
}

function le_admin_preprocess_page(&$variables)
{
  // hide secondary toolbar
  unset($variables['page']['gin_secondary_toolbar']);
}

function le_admin_preprocess_node(&$variables)
{
  $node = $variables['node'];
  $nid = $node->id();
  $node_type = $node->getType();
  if ($node_type == 'le_akteur') {
    $events_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'le_event')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();
    
    $events_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'le_event')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();

    $projects_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'project')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();

    $projects_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'project')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();

    $blog_articles_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'blog_article')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();

    $blog_articles_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'blog_article')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();

    $partners_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'partner')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();

    $partners_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'partner')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();

    $sponsors_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'sponsor')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();

    $sponsors_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'sponsor')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();

    $webbuilders_published_count = \Drupal::entityQuery('node')
    ->condition('type', 'webbuilder')
    ->condition('og_audience', $nid)
    ->condition('status', 1)
    ->count()
    ->execute();

    $webbuilders_unpublished_count = \Drupal::entityQuery('node')
    ->condition('type', 'webbuilder')
    ->condition('og_audience', $nid)
    ->condition('status', 0)
    ->count()
    ->execute();
    
    $variables['events'] = [
      'published_count' => intval($events_published_count),
      'unpublished_count' => intval($events_unpublished_count),
    ];
    $variables['projects'] = [
      'published_count' => intval($projects_published_count),
      'unpublished_count' => intval($projects_unpublished_count),
    ];
    $variables['blog_articles'] = [
      'published_count' => intval($blog_articles_published_count),
      'unpublished_count' => intval($blog_articles_unpublished_count),
    ];
    $variables['partners'] = [
      'published_count' => intval($partners_published_count),
      'unpublished_count' => intval($partners_unpublished_count),
    ];
    $variables['sponsors'] = [
      'published_count' => intval($sponsors_published_count),
      'unpublished_count' => intval($sponsors_unpublished_count),
    ];
    $variables['webbuilders'] = [
      'published_count' => intval($webbuilders_published_count),
      'unpublished_count' => intval($webbuilders_unpublished_count),
    ];
  }
}