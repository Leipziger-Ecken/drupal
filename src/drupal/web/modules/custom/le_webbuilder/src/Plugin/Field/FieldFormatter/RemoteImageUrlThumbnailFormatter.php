<?php

namespace Drupal\le_webbuilder\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Remote image thumbnail URL field formatter.
 *
 * @FieldFormatter(
 *   id = "remote_image_url_thumbnail_formatter",
 *   label = @Translation("Thumbnail with metatags"),
 *   description = @Translation("Display the remote image thumbnail along with its associated metatags."),
 *   field_types = {
 *     "remote_image_url"
 *   }
 * )
 */
class RemoteImageUrlThumbnailFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '100',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'number',
      '#title' => t('Width'),
      '#field_suffix' => t('px'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    if (!empty($settings['width'])) {
      $summary[] = t('Width @width px', ['@width' => $settings['width']]);
    }

    return $summary;
  }

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();
    $width = $this->getSetting('width');

    foreach ($elements as $delta => $entity) {
      $uri = str_replace('.jpg', '&w=' . $width, $values[$delta]['uri']);
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => "<img src='{$uri}' alt='{$values[$delta]['alt']}'>",
        '#allowed_tags' => ['img'],
      ];
    }

    return $elements;
  }

}
