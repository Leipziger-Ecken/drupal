<?php

namespace Drupal\obfuscate_email\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide a filter to obfuscate mailto anchor tags and optionally replace inner text.
 *
 * @Filter(
 *   id = "obfuscate_email",
 *   title = @Translation("Obfuscate Email"),
 *   description = @Translation("Transform <code>mailto</code> anchors into obfuscated markup."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "click" = FALSE,
 *     "click_label" = @Translation("Click here to show mail address"),
 *   },
 * )
 */
class ObfuscateEmail extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['click'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force user to click link to display mail address.'),
      '#default_value' => $this->settings['click'],
    ];

    $form['click_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to show on link'),
      '#default_value' => $this->settings['click_label'],
      '#states' => [
        'visible' => [
          ':input[name="filters[obfuscate_email][settings][click]"]' => array('checked' => TRUE),
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'mailto') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    /** @var \DOMElement $domElement */
    foreach ($xpath->query('//a[starts-with(@href, "mailto:")]') as $domElement) {
      // Read the href attribute value and delete it.
      $href = str_replace('mailto:', '', $domElement->getAttribute('href'));
      $domElement->setAttribute('href', '#');

      // Convert to rot13
      $mail_string = str_rot13(str_replace(['.', '@'], ['/dot/', '/at/'], $href));
      $domElement->setAttribute('data-mail-to', $mail_string);

      // Replace occurrence of the address in the anchor text.
      if (strpos($domElement->nodeValue, $href) !== FALSE) {
        $domElement->nodeValue = str_replace($href, '@email', $domElement->nodeValue);
        $domElement->setAttribute('data-replace-inner', '@email');

        if ($this->settings['click']) {
          $domElement->setAttribute('data-mail-click-link', true);
          $domElement->nodeValue = $this->t($this->settings['click_label']);
          $domElement->setAttribute('data-replace-inner', $domElement->nodeValue);
        }

      }
    }
    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

}
