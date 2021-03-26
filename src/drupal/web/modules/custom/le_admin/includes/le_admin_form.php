<?php

use \Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\le_admin\Controller\ApiController;

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

  if (in_array($form_id, [
    'node_partner_edit_form',
    'node_partner_form',
  ])) {
    // add custom submit handler
    $form['actions']['submit']['#submit'][] = 'le_admin_partner_submit';
  }

  if ($form_id === 'node_webbuilder_page_delete_form') {
    // add custom submit handler, to handle reassignment of child pages
    $form['actions']['submit']['#submit'][] = 'le_admin_webbuilder_page_delete_submit';
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
  
  // adds required asteriks description to all forms
  if ($form_id !== 'views_exposed_form') {
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
  if ($form_id === 'node_webbuilder_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View website'),
        'webbuilder_' . $entity->id()
      ),
      '#weight' => -10,
    ];

    $form['meta']['le_admin_user_webbuilder_pages'] = [
      '#type' => 'link',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_webbuilder_pages', ['node' => $entity->id()]),
        t('Manage pages')
      ),
      '#weight' => -9,
    ];
  }
}

function _le_admin_webbuilder_page_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  // hide parent and weight fields, as these are set automaticly
  $form['field_weight']['#attributes']['class'][] = 'hidden';
  $form['field_parent']['#attributes']['class'][] = 'hidden';

  if ($form_id === 'node_webbuilder_page_form') {
    $parent_id = \Drupal::request()->query->get('parent_page');
    $sibling_id = \Drupal::request()->query->get('sibling_page');

    if ($parent_id) {
      $form['field_parent']['widget']['#default_value'] = [$parent_id];
    }
    
    $form['sibling_id'] = [
      '#type' => 'hidden',
      '#value' => $sibling_id,
    ];
    
    $form['actions']['submit']['#submit'][] = 'le_admin_webbuilder_page_submit';
  }
  if ($form_id === 'node_webbuilder_page_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['meta']['le_admin_node_view'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
        t('View page'),
        'webbuilder_page_' . $entity->id()
      ),
      '#weight' => -10,
    ];

    $form['meta']['le_admin_user_webbuilder'] = [
      '#type' => 'item',
      '#markup' => _le_admin_sidebar_link(
        Url::fromRoute('le_admin.user_akteur_webbuilder', ['node' => $entity->og_audience[0]->target_id]),
        t('Manage website')
      ),
      '#weight' => -9,
    ];
  }
}

function _le_admin_event_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_le_event_form') {
    // prefill address from akteur
    $akteur_id = \Drupal::request()->query->get('le_akteur');
    if ($akteur_id) {
      $akteur = \Drupal::entityTypeManager()->getStorage('node')->load($akteur_id);
      if ($akteur) {
        $form['field_adresse']['widget'][0]['address']['#default_value'] = $akteur->field_adresse[0]->getValue();
        $form['field_bezirk']['widget']['#default_value'][] = $akteur->field_bezirk[0]->getValue()['target_id'];
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