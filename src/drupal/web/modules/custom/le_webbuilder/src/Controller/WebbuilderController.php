<?php namespace Drupal\le_webbuilder\Controller;

use Drupal\Core\Controller\ControllerBase;

class WebbuilderController extends ControllerBase
{
  /**
  * Creates a new webbuilder from a preset
  */
  public function createFromPreset($webbuilder_preset)
  {
    $user_id = \Drupal::currentUser()->id();
    $akteur_id = \Drupal::request()->get('le_akteur') ?? $webbuilder_preset->og_audience->target_id;
    $destination = \Drupal::request()->get('_destination');
    
    // deep clone the webbuilder
    $webbuilder = $webbuilder_preset->createDuplicate();
    $webbuilder_preset_id = $webbuilder_preset->id();
    
    // set current user as author and unpublish it
    $webbuilder->uid = $user_id;
    $webbuilder->status = 0;
    $webbuilder->published_at = null;
    $webbuilder->field_is_preset[0]->value = false;
    
    // set the og_audience if given
    if ($akteur_id) {
      $webbuilder->og_audience[0]->target_id = $akteur_id;
    }

    // remember the original frontpage
    $frontpage_id = null;
    $preset_frontpage_id = null;
    
    if ($webbuilder_preset->field_frontpage[0]) {
      $preset_frontpage_id = $webbuilder_preset->field_frontpage[0]->target_id;
    };

    // now save the webbuilder
    $webbuilder->save();
    $webbuilder_id = $webbuilder->id();
    
    // load preset pages
    $pages_query = \Drupal::entityQuery('node');
    $pages_query->condition('field_webbuilder', $webbuilder_preset_id);
    $result = $pages_query->execute();
    $preset_pages = [];
    foreach($result as $nid) {
      $preset_pages[$nid] = \Drupal::entityManager()->getStorage('node')->load($nid);
    }
    
    // now clone the pages and the paragraphs
    foreach($preset_pages as $nid => $page) {
      $cloned_page = $page->createDuplicate();
      $cloned_page->field_webbuilder[0]->target_id = $webbuilder_id;
      $cloned_page->og_audience[0]->target_id = $akteur_id;      
      $cloned_page->uid = $user_id;

      // clone paragraphs
      foreach($cloned_page->field_contents as $paragraph) {
        $paragraph->entity = $paragraph->entity->createDuplicate();
        $paragraph->entity->uid = $user_id;
      }
      
      $cloned_page->save();
      
      // if the original page is the frontpage,
      // use the ID of the cloned page and set it as the frontpage
      // for the new webbuilder
      // we have to do non strict equality here, as IDs can be ints or strings
      if ($nid == $preset_frontpage_id) {
        $frontpage_id = $cloned_page->id();
      }
    }
    
    // set the new frontpage id
    if ($frontpage_id) {
      $webbuilder->field_frontpage->target_id = $frontpage_id;
      
      // save again, to store new frontpage
      $webbuilder->save();
    }  
    
    return $this->redirect('entity.node.edit_form', [
      'node' => $webbuilder->id(),
    ], [
      'query' => ['destination' => $destination]
    ]);
  }
}