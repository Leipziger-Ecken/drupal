<?php

use \Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\le_admin\Controller\ApiController;
use Drupal\views\Views;

function _le_admin_sidebar_link($url, $label, $target = null) 
{
  return '<a class="link" href="' . $url->toString() . '"' . ($target ? ' target="' . $target . '"' : '') . '>' . $label . '</a>';
}

function le_admin_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if (strpos($form_id, 'node_') === 0 && strpos($form_id, 'delete_form') === false) {
    _le_admin_node_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_le_akteur_edit_form',
    'node_le_akteur_form',
  ])) {
    _le_admin_akteur_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_le_event_edit_form',
    'node_le_event_form',
  ])) {
    _le_admin_event_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_project_edit_form',
    'node_project_form',
  ])) {
    _le_admin_project_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_blog_article_edit_form',
    'node_blog_article_form',
  ])) {
    _le_admin_blog_article_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_webbuilder_edit_form',
    'node_webbuilder_form',
  ])) {
    _le_admin_webbuilder_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_webbuilder_page_edit_form',
    'node_webbuilder_page_form',
  ])) {
    _le_admin_webbuilder_page_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, ['media_unsplash_image_add_form', 'media_unsplash_image_edit_form'])) {
    _le_admin_media_unsplash_image_form_alter($form, $form_state, $form_id);
  }

  if (in_array($form_id, [
    'node_partner_edit_form',
    'node_partner_form',
  ])) {
    $form['#attached']['library'][] = 'le_admin/partner_form';
    // add custom submit handler
    $form['actions']['submit']['#submit'][] = 'le_admin_partner_submit';
  }

  if ($form_id === 'node_webbuilder_page_delete_form') {
    // add custom submit handler, to handle reassignment of child pages
    $form['actions']['submit']['#submit'][] = 'le_admin_webbuilder_page_delete_submit';
  }

  if ($form_id === 'node_webbuilder_delete_form') {
    // add custom submit handler, to delete all pages
    $form['actions']['submit']['#submit'][] = 'le_admin_webbuilder_delete_submit';
  }

  if (in_array($form_id, [
    'node_le_event_form',
    'node_le_event_edit_form',
    'node_blog_article_form',
    'node_blog_article_edit_form',
    'node_project_form',
    'node_project_edit_form',
    'node_partner_form',
    'node_partner_edit_form',
    'node_sponsor_form',
    'node_sponsor_edit_form',
    'node_webbuilder_form',
    'node_webbuilder_edit_form',
  ])) {
    _le_admin_og_audience_form_alter($form, $form_state, $form_id);
  }

  if ($form_id === 'user_login_form') {
    $form['#submit'][] = 'le_admin_user_login_form_submit';
  }
  
  if (in_array($form_id, [
    'user_login_form',
    'user_register_form',
    'user_pass',
  ])) {
    _le_admin_login_form_alter($form, $form_state, $form_id);
  }
  
  if ($form_id === 'user_pass_reset') {
    _le_admin_user_pass_reset_form_alter($form, $form_state, $form_id);
  }

  if ($form_id === 'user_form' && \Drupal::request()->query->get('pass-reset-token')) {
    $form['actions']['submit']['#submit'][] = 'le_admin_user_form_after_pass_reset_submit';
  }
  if ($form_id === 'media_library_add_form_upload') {
    _le_admin_form_media_upload_alter($form, $form_state, $form_id);
  }
  
  if (in_array($form_id, [
    'media_document_add_form',
    'media_document_edit_form',
    'media_image_add_form',
    'media_image_edit_form',
    'media_remote_image_add_form',
    'media_remote_image_edit_form',
    'media_unsplash_image_add_form',
    'media_unsplash_image_edit_form',
    'media_audio_add_form',
    'media_audio_edit_form',
    'media_remote_audio_add_form',
    'media_remote_audio_edit_form',
    'media_remote_video_add_form',
    'media_remote_video_edit_form',
  ])) {
    _le_admin_form_media_alter($form, $form_state, $form_id);
  }

  if ($form_id === 'views_exposed_form') {
    if (in_array($form['#id'], [
      'views-exposed-form-media-media-page-list',
      'views-exposed-form-media-library-page',
      'views-exposed-form-media-library-widget',
      'views-exposed-form-media-library-widget-table',
    ])) {
      _le_admin_form_media_view_alter($form, $form_state, $form_id);
    }
  } 
  // adds required asteriks description to all forms
  else {
    $form['required_help'] = [
      '#type' => 'markup',
      '#markup' => '<p class="form-item__description">* ' . t('Required field') . '</p>',
      '#weight' => 1000,
    ];
  }
}

function le_admin_user_login_form_submit(&$form, FormStateInterface $form_state)
{
  // Check if a destination was set, probably on an exception controller.
  // @see \Drupal\user\Form\UserLoginForm::submitForm()
  $request = \Drupal::service('request_stack')->getCurrentRequest();
  if ($request->request->has('destination')) {
    $request->query->set('destination', $request->request->get('destination'));
  } else {
    $form_state->setRedirect('le_admin.user_dashboard');
  }
}

function _le_admin_akteur_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['#attached']['library'][] = 'le_admin/akteur_form';

  if ($form_id === 'node_le_akteur_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();
    
    $form['meta']['le_admin_akteur_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View public profile'),
        'akteur_' . $entity->id()
      ),
      '#weight' => -10,
    ];

    $form['meta']['le_admin_user_akteur'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_akteur', ['node' => $entity->id()]),
        t('Manage contents')
      ),
      '#weight' => -9,
    ];

    $form['meta']['le_admin_user_akteur_webbuilder'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_akteur_webbuilder', ['node' => $entity->id()]),
        t('Manage Website')
      ),
      '#weight' => -8,
    ];
  }
}

function _le_admin_webbuilder_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $user = \Drupal::currentUser();
  $roles = $user->getRoles();

  // remove preset field for regular users
  if (!in_array('le_role_redakteur', $roles) && !in_array('administrator', $roles)) {
    unset($form['field_is_preset']);
    unset($form['field_description']);
    unset($form['field_preview_image']);
    unset($form['fieldgroups']['group_preset']);
  }

  if ($form_id === 'node_webbuilder_edit_form') {
    $webbuilder = $form_state->getFormObject()->getEntity();
    if (!$webbuilder) {
      return;
    }
    $webbuilder_id = $webbuilder->id();

    $form['field_frontpage']['widget']['#options'] = [];

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $webbuilder->id()]),
        t('View website'),
        'webbuilder_' . $webbuilder->id()
      ),
      '#weight' => -10,
    ];

    $form['meta']['le_admin_user_webbuilder_pages'] = [
      '#type' => 'link',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_webbuilder_pages', ['node' => $webbuilder->id()]),
        t('Manage pages')
      ),
      '#weight' => -9,
    ];

    // preload the page tree for the frontpage selection
    $view = Views::getView('webbuilder_pages');
    $view->setDisplay('entity_reference_page_tree');
    $view->setArguments([$webbuilder_id]);
    $result = $view->render();

    foreach ($result as $nid => $row) {
      if (isset($row['#row']) && isset($row['#row']->_entity)) {
        $title = $row['#row']->_entity->title[0]->value;
        $_row = $row;

        while ($_row !== null) {
          if ($_row && isset($_row['#row']->_entity->field_parent[0])) {
            $parent_id = $_row['#row']->_entity->field_parent[0]->target_id;
            $title = '- ' . $title;
            $_row = $result[$parent_id] ?? null;
          } else {
            $_row = null;
          }
        }
        $form['field_frontpage']['widget']['#options'][$nid] = $title;
      }
    }
  }
}

function _le_admin_webbuilder_page_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['#attached']['library'][] = 'le_admin/webbuilder_page_form';

  // hide parent and weight fields, as these are set automaticly
  $form['field_weight']['#attributes']['class'][] = 'hidden';
  $form['field_parent']['#attributes']['class'][] = 'hidden';

  // create form
  if ($form_id === 'node_webbuilder_page_form') {
    $parent_id = \Drupal::request()->query->get('parent_page');
    $sibling_id = \Drupal::request()->query->get('sibling_page');
    $webbuilder_id = \Drupal::request()->query->get('webbuilder');

    // hide webbuilder and og_audience fields
    $form['field_webbuilder']['#attributes']['class'][] = 'hidden';
    $form['og_audience']['#attributes']['class'][] = 'hidden';

    if ($webbuilder_id) {
      $webbuilder = \Drupal::entityTypeManager()->getStorage('node')->load($webbuilder_id);
      if ($webbuilder) {
        $form['field_webbuilder']['widget']['#options'] = [];
        $form['field_webbuilder']['widget']['#options'][$webbuilder_id] = $webbuilder->title[0]->value;
        $form['field_webbuilder']['widget']['#default_value'] = $webbuilder_id;

        if (isset($webbuilder->og_audience[0])) {
          $akteur_id = $webbuilder->og_audience[0]->target_id;
          $form['og_audience']['widget']['#default_value'] = $akteur_id;
        }
      }
    }

    if ($parent_id) {
      $form['field_parent']['widget']['#default_value'] = [$parent_id];
    }
    
    $form['sibling_id'] = [
      '#type' => 'hidden',
      '#value' => $sibling_id,
    ];
    
    $form['actions']['submit']['#submit'][] = 'le_admin_webbuilder_page_submit';
  }

  // edit form
  if ($form_id === 'node_webbuilder_page_edit_form') {
    $page = $form_state->getFormObject()->getEntity();

    // remove webbuilder and og_audience fields
    unset($form['field_webbuilder']);
    unset($form['og_audience']);

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $page->id()]),
        t('View page'),
        'webbuilder_page_' . $page->id()
      ),
      '#weight' => -10,
    ];

    $form['meta']['le_admin_user_webbuilder'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_akteur_webbuilder', ['node' => $page->og_audience[0]->target_id]),
        t('Manage website')
      ),
      '#weight' => -9,
    ];

    $webbuilder_id = $page->field_webbuilder[0]->target_id;

    if (!$webbuilder_id) {
      return;
    }

    $webbuilder = \Drupal::entityTypeManager()->getStorage('node')->load($webbuilder_id);
    if (!$webbuilder) {
      return;
    }

    // load the page tree
    $form['field_parent']['widget']['#options'] = [];
    $view = Views::getView('webbuilder_pages');
    $view->setDisplay('entity_reference_page_tree');
    $view->setArguments([$webbuilder_id]);
    $result = $view->render();

    foreach ($result as $nid => $row) {
      if (isset($row['#row']) && isset($row['#row']->_entity)) {
        $title = $row['#row']->_entity->title[0]->value;
        $_row = $row;

        while ($_row !== null) {
          if ($_row && isset($_row['#row']->_entity->field_parent[0])) {
            $parent_id = $_row['#row']->_entity->field_parent[0]->target_id;
            $title = '- ' . $title;
            $_row = $result[$parent_id] ?? null;
          } else {
            $_row = null;
          }
        }
        $form['field_parent']['widget']['#options'][$nid] = $title;
      }
    }
  }
}

function _le_admin_event_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_le_event_form') {
    // prefill address, categories and target groups from akteur
    $akteur_id = \Drupal::request()->query->get('le_akteur');
    if ($akteur_id) {
      $akteur = \Drupal::entityTypeManager()->getStorage('node')->load($akteur_id);
      if ($akteur) {
        $form['field_adresse']['widget'][0]['address']['#default_value'] = $akteur->field_adresse[0]->getValue();
        $form['field_bezirk']['widget']['#default_value'][] = $akteur->field_bezirk[0]->getValue()['target_id'];
        
        $form['field_le_event_kategorie_typ']['widget']['#default_value'] = array_map(function ($item) {
          return $item['target_id'];
        }, $akteur->field_le_akteur_kategorie_typ->getValue());

        $form['field_le_event_kategorie_gruppe']['widget']['#default_value'] = array_map(function ($item) {
          return $item['target_id'];
        }, $akteur->field_le_akteur_kategorie_gruppe->getValue());
      }
    }
  }
  if ($form_id === 'node_le_event_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View event'),
        'le_event_' . $entity->id()
      ),
      '#weight' => -10,
    ];
  }
}

function _le_admin_project_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_project_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View project'),
        'le_project_' . $entity->id()
      ),
      '#weight' => -10,
    ];
  }
}

function _le_admin_blog_article_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_blog_article_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View blog article'),
        'blog_article_' . $entity->id()
      ),
      '#weight' => -10,
    ];
  }
}

function _le_admin_og_audience_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $user = \Drupal::currentUser();
  $roles = $user->getRoles();
  
  if (strpos($form_id, 'edit_form') === false) {
    // hide og_audience field for regular users
    if (!in_array('administrator', $roles)) {
      $form['og_audience']['#attributes']['class'][] = 'hidden';
    }

    $akteur_id = \Drupal::request()->query->get('le_akteur');
    if ($akteur_id) {
      $form['og_audience']['widget']['#default_value'] = $akteur_id;
    }
  } else {
    // remove og_audience field for regular users
    if (!in_array('administrator', $roles)) {
      unset($form['og_audience']);
    }
  }
}

function _le_admin_node_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['#attached']['library'][] = 'le_admin/tailwind';

  // allow access to publish
  $form['status']['#access'] = true;

  // add back action
  $destination = \Drupal::request()->query->get('destination');
  $form['le_admin_back'] = [
    '#type' => 'link',
    '#title' => '< ' . t('Back'),
    '#url' => $destination ? Url::fromUri('internal:' . $destination) : Url::fromRoute('le_admin.user_dashboard'),
    '#attributes' => [
      'class' => le_admin_link_button_classes,
    ],
    '#weight' => -10,
  ];
}

function le_admin_webbuilder_page_submit(array $form, FormStateInterface $form_state)
{
  $page = $form_state->getFormObject()->getEntity();
  $parent = $page->get('field_parent');
  $parent_id = null;
  $sibling_id = $form_state->getValue('sibling_id');
  
  if ($parent && $parent[0]) {
    $parent_id = $parent[0]->target_id;
  }

  ApiController::sortPage($page, $parent_id, $sibling_id);
}

function le_admin_webbuilder_page_delete_submit(array $form, FormStateInterface $form_state)
{
  $page = $form_state->getFormObject()->getEntity();
  if (!$page) {
    return;
  }

  // load child pages
  $children_query = \Drupal::entityQuery('node');
  $children_query->condition('type', 'webbuilder_page');
  $children_query->condition('field_parent', $page->id());
  $result = $children_query->execute();

  // and remove parent reference
  foreach ($result as $nid) {
    $child_page = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $child_page->set('field_parent', null);
    $child_page->save();
  }
}

function _le_admin_login_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['image_attribution'] = [
    '#type' => 'markup',
    '#markup' => '<p class="form-item__description">Photo by <a href="https://unsplash.com/@shinychunks?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText">Chris Unger</a> on <a href="/?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText">Unsplash</a></p>',
    '#weight' => 1100,
  ];
  $form['#attached']['library'][] = 'le_admin/login';
}

function _le_admin_user_pass_reset_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['actions']['submit']['#value'] = t('Reset password');
}

function le_admin_partner_submit(array $form, FormStateInterface $form_state)
{
  $entity = $form_state->getFormObject()->getEntity();
  $partner_type = $form_state->getValue('field_partner_type');
  
  if ($partner_type && isset($partner_type[0])) {
    $partner_type = $partner_type[0]['value'];
  }
  if ($partner_type === 'le_akteur') {
    $akteur_id = $form_state->getValue('field_akteur');
    if ($akteur_id && isset($akteur_id[0])) {
      $akteur_id = $akteur_id[0]['target_id'];
      $akteur = \Drupal::entityTypeManager()->getStorage('node')->load($akteur_id);
      
      if ($akteur) {
        $form_state->setValue('title', $akteur->getTitle());
        $entity->set('title', $akteur->getTitle());
        $entity->save();
      }
    }   
  }
}

function _le_admin_media_unsplash_image_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $form['#attached']['library'][] = 'le_admin/unsplash_media_form';
  $form['search'] = [
    '#type' => 'search',
    '#title' => t('Search images'),
    '#placeholder' => t('Enter search terms'),
    '#attributes' => [
      'data-results-target' => '#edit-field-unsplash-search-results',
      'data-url-target' => '#edit-field-media-remote-image-0-uri',
      'data-alt-target' => '#edit-field-media-remote-image-0-alt',
      'data-title-target' => '#edit-name-0-value',
      'data-attribution-target' => '#edit-field-attribution-0-value',
      'data-api-url' => '/api/unsplash',
      'oninput' => 'handleUnsplashSearchInput(this)',
      'autocomplete' => 'off',
    ],
    '#weight' => -100,
  ];
  $form['search_results'] = [
    '#type' => 'markup',
    '#markup' => '<div class="unsplash-results" id="edit-field-unsplash-search-results"></div>',
    '#weight' => -99,
  ];
  $form['image_preview'] = [
    '#type' => 'markup',
    '#markup' => '<figure class="unsplash-preview" id="edit-field-image-preview" data-url-source="#edit-field-media-remote-image-0-uri" data-alt-source="#edit-field-media-remote-image-0-alt"></figure>',
    '#weight' => -98,
  ];

  // hide attribution field, so user cannot enter values
  $form['field_attribution']['#attributes']['class'][] = 'hidden';
}

function _le_admin_form_media_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $user = \Drupal::currentUser();

  // reset options
  $form['field_og_audience']['widget']['#options'] = [];

  if ($user->hasPermission('edit any le_akteur content')) {
    $form['field_og_audience']['widget']['#options']['_none'] = t('None');
  }

  $view = Views::getView('le_verwaltete_akteure');
  $view->setDisplay('entity_reference');
  $result = $view->render();

  foreach ($result as $nid => $row) {
    if (isset($row['#row']) && isset($row['#row']->_relationship_entities) && isset($row['#row']->_relationship_entities['uid'])) {
      $title = $row['#row']->_relationship_entities['uid']->title[0]->value;

      $form['field_og_audience']['widget']['#options'][$nid] = $title;
    }
  }

  if (!$user->hasPermission('edit any le_akteur content')) {
    $form['field_og_audience']['widget']['#default_value'] = array_keys($form['field_og_audience']['widget']['#options'])[0] . '';
    $form['field_og_audience']['widget']['#required'] = true;
  }
}

function _le_admin_form_media_upload_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $user = \Drupal::currentUser();

  // reset options
  if (!isset($form['media'][0])) {
    return;
  }

  $form['media'][0]['fields']['field_og_audience']['widget']['#options'] = [];

  if ($user->hasPermission('edit any le_akteur content')) {
    $form['media'][0]['fields']['field_og_audience']['widget']['#options']['_none'] = t('None');
  }

  $view = Views::getView('le_verwaltete_akteure');
  $view->setDisplay('entity_reference');
  $result = $view->render();

  foreach ($result as $nid => $row) {
    if (isset($row['#row']) && isset($row['#row']->_relationship_entities) && isset($row['#row']->_relationship_entities['uid'])) {
      $title = $row['#row']->_relationship_entities['uid']->title[0]->value;

      $form['media'][0]['fields']['field_og_audience']['widget']['#options'][$nid] = $title;
    }
  }

  if (!$user->hasPermission('edit any le_akteur content')) {
    $form['media'][0]['fields']['field_og_audience']['widget']['#default_value'] = array_keys($form['media'][0]['fields']['field_og_audience']['widget']['#options']['#options'])[0] . '';
    $form['media'][0]['fields']['field_og_audience']['widget']['#required']= true;
  }
}

function _le_admin_form_media_view_alter(&$form, FormStateInterface $form_state, $form_id)
{
  $user = \Drupal::currentUser();
  $form['field_og_audience_target_id']['#type'] = 'select';
  unset($form['field_og_audience_target_id']['#size']);
  
  if ($user->hasPermission('edit any le_akteur content')) {
    $form['field_og_audience_target_id']['#options'][''] = t('Any');
  }

  $view = Views::getView('le_verwaltete_akteure');
  $view->setDisplay('entity_reference');
  $result = $view->render();
  
  foreach ($result as $nid => $row) {
    if (isset($row['#row']) && isset($row['#row']->_relationship_entities) && isset($row['#row']->_relationship_entities['uid'])) {
      $title = $row['#row']->_relationship_entities['uid']->title[0]->value;

      $form['field_og_audience_target_id']['#options'][$nid] = $title;
    }
  }

  if (!$user->hasPermission('edit any le_akteur content')) {
    $form['field_og_audience_target_id']['#default_value'] = array_keys($form['field_og_audience_target_id']['#options'])[0] . '';
    $form['field_og_audience_target_id']['#required'] = true;
  }
}

function le_admin_webbuilder_delete_submit(array $form, FormStateInterface $form_state)
{
  $webbuilder = $form_state->getFormObject()->getEntity();
  if (!$webbuilder) {
    return;
  }

  // load pages
  $pages_query = \Drupal::entityQuery('node');
  $pages_query->condition('type', 'webbuilder_page');
  $pages_query->condition('field_webbuilder', $webbuilder->id());
  $result = $pages_query->execute();
  foreach ($result as $nid) {
    $page = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $page->delete();
  }
}

function le_admin_user_form_after_pass_reset_submit(array $form, FormStateInterface $form_state)
{
  $form_state->setRedirect('le_admin.user_dashboard');
}