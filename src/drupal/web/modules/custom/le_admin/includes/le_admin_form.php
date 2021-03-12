<?php

use \Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
    'use_pass',
  ])) {
    $form['#attached']['library'][] = 'le_admin/login';
  }

  // adds required asteriks description to all forms
  $form['required_help'] = [
    '#type' => 'markup',
    '#markup' => '<p class="form-item__description">* ' . t('Required field') . '</p>',
    '#weight' => 1000,
  ];
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

    $form['le_admin_akteur_view'] = [
      '#type' => 'link',
      '#title' => t('Öffentliches Profil öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'akteur_' . $entity->id(),
      ],
      '#weight' => -10,
    ];

    $form['le_admin_user_akteur'] = [
      '#type' => 'link',
      '#title' => t('Inhalte verwalten'),
      '#url' => Url::fromRoute('le_admin.user_akteur', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
      ],
      '#weight' => -9,
    ];

    $form['le_admin_user_akteur_webbuilder'] = [
      '#type' => 'link',
      '#title' => t('Webbaukasten'),
      '#url' => Url::fromRoute('le_admin.user_akteur_webbuilder', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
      ],
      '#weight' => -8,
    ];
  }
}

function _le_admin_webbuilder_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_webbuilder_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['le_admin_node_view'] = [
      '#type' => 'link',
      '#title' => t('Webseite öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'webbuilder_' . $entity->id(),
      ],
      '#weight' => -10,
    ];

    $form['le_admin_user_webbuilder_pages'] = [
      '#type' => 'link',
      '#title' => t('Seiten verwalten'),
      '#url' => Url::fromRoute('le_admin.user_webbuilder_pages', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
      ],
      '#weight' => -9,
    ];
  }
}

function _le_admin_webbuilder_page_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_webbuilder_page_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['le_admin_node_view'] = [
      '#type' => 'link',
      '#title' => t('Seite öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'webbuilder_page_' . $entity->id(),
      ],
      '#weight' => -10,
    ];

    $form['le_admin_user_webbuilder'] = [
      '#type' => 'link',
      '#title' => t('Webbaukasten verwalten'),
      '#url' => Url::fromRoute('le_admin.user_akteur_webbuilder', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
      ],
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
      $akteur = \Drupal::entityManager()->getStorage('node')->load($akteur_id);
      if ($akteur) {
        $form['field_adresse']['widget'][0]['address']['#default_value'] = $akteur->field_adresse[0]->getValue();
        $form['field_bezirk']['widget']['#default_value'][] = $akteur->field_bezirk[0]->getValue()['target_id'];
      }
    }
  }
  if ($form_id === 'node_le_event_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['le_admin_node_view'] = [
      '#type' => 'link',
      '#title' => t('Event öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'le_event_' . $entity->id(),
      ],
      '#weight' => -10,
    ];
  }
}

function _le_admin_project_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_project_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['le_admin_node_view'] = [
      '#type' => 'link',
      '#title' => t('Projekt öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'le_event_' . $entity->id(),
      ],
      '#weight' => -10,
    ];
  }
}

function _le_admin_blog_article_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if ($form_id === 'node_blog_article_edit_form') {
    $entity = $form_state->getFormObject()->getEntity();

    $form['le_admin_node_view'] = [
      '#type' => 'link',
      '#title' => t('Blog-Artikel öffnen'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]),
      '#attributes' => [
        'class' => le_admin_link_button_classes,
        'target' => 'le_event_' . $entity->id(),
      ],
      '#weight' => -10,
    ];
  }
}

function _le_admin_og_audience_form_alter(&$form, FormStateInterface $form_state, $form_id)
{
  if (strpos($form_id, 'edit_form') === false) {
    // hide og_audience field
    $form['og_audience']['#attributes']['class'][] = 'hidden';

    $akteur_id = \Drupal::request()->query->get('le_akteur');
    if ($akteur_id) {
      $form['og_audience']['widget']['#default_value'] = $akteur_id;
    }
  } else {
    // remove og_audience field
    unset($form['og_audience']);
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
