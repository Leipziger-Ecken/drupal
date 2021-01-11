<?php

namespace Drupal\maillog\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("maillog_field_delete")
 */
class MaillogFieldDelete extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Ensure user has permission to delete.
    if (!\Drupal::currentUser()->hasPermission('delete maillog')) {
      return;
    }

    $id = $this->getValue($values);

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('delete');

    return \Drupal::service('link_generator')->generate($text, 'maillog.delete', ['maillog_id' => $id], ['query' => \Drupal::destination()->getAsArray()]);
  }

}

