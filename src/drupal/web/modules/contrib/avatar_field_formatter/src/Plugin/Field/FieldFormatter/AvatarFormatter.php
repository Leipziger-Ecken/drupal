<?php

namespace Drupal\avatar_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\user\Entity\User;
use Laravolt\Avatar\Avatar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the Avatar Field Formatter'.
 *
 * @FieldFormatter(
 *   id = "avatar_formatter_default",
 *   label = @Translation("Avatar default"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class AvatarFormatter extends ImageFormatter {

  /**
   * The transliteration manager.
   *
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Transliteration\PhpTransliteration $transliteration
   *   The transliteration manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, PhpTransliteration $transliteration) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('transliteration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'avatar_height' => '64',
      'avatar_width' => '64',
      'avatar_chars' => 1,
      'avatar_backgrounds' => "#f44336\n#E91E63\n#9C27B0\n#673AB7\n#3F51B5\n#2196F3\n#03A9F4\n#00BCD4\n#009688\n#4CAF50\n#8BC34A\n#CDDC39\n#FFC107\n#FF9800\n#FF5722",
      'avatar_font_family' => '"Open Sans","Microsoft YaHei","微软雅黑",STXihei,"华文细黑",serif',
      'avatar_font_size' => 36,
      'avatar_uppercase' => TRUE,
      'avatar_svg' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::settingsForm($form, $form_state);

    $element['avatar_width'] = [
      '#title' => $this->t('Avatar Width (px)'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('avatar_width'),
      '#min' => 1,
    ];

    $element['avatar_height'] = [
      '#title' => $this->t('Avatar Height (px)'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('avatar_height'),
      '#min' => 1,
    ];

    $element['avatar_font_family'] = [
      '#title' => $this->t('Font-Family'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('avatar_font_family'),
      '#description' => $this->t('The proper font family to support non-English Characters'),
    ];

    $element['avatar_backgrounds'] = [
      '#title' => $this->t('Background Colors Available'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('avatar_backgrounds'),
      '#description' => $this->t('One hex format color per row'),
      '#rows' => 15,
    ];
    $element['avatar_svg'] = [
      '#title' => $this->t('Use SVG'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('avatar_svg'),
    ];

    $element['avatar_uppercase'] = [
      '#title' => $this->t('Uppercase Chars'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('avatar_uppercase'),
    ];

    $element['avatar_chars'] = [
      '#title' => $this->t('Number of Characters'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('avatar_chars'),
      '#min' => 1,
    ];

    $element['avatar_font_size'] = [
      '#title' => $this->t('Font Size'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('avatar_font_size'),
      '#min' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);

    if (empty($elements)) {
      $entity = $items->getEntity();
      if ($entity instanceof User) {
        $display_name = $entity->getDisplayName();
      }
      else {
        $display_name = $entity->label();
      }
      $image_link_setting = $this->getSetting('image_link');
      // Check if the formatter involves a link.
      $url = '';
      if (($image_link_setting === 'content') && !$entity->isNew()) {
        $url = $entity->toUrl();
      }

      $use_svg = $this->getSetting('avatar_svg');

      $avatar = new Avatar($this->getAvatarConfig());

      if ($use_svg) {
        $svg = $avatar->create($display_name)->toSvg();
        $data = 'data:image/svg+xml;base64,' . base64_encode($svg);
      }
      else {
        $data = $avatar->create($display_name)->toBase64();
      }

      if (!empty($url)) {
        $elements[] = [
          '#type' => 'inline_template',
          '#template' => '<a href="{{ url }}"><img src="{{ data }}" alt="{{ alt }}"/></a>',
          '#context' => [
            'data' => $data,
            'alt' => $display_name,
            'url' => $url,
          ],
        ];
      }
      else {
        $elements[] = [
          '#type' => 'inline_template',
          '#template' => '<img src="{{ data }}" alt="{{ alt }}"/>',
          '#context' => [
            'data' => $data,
            'alt' => $display_name,
          ],
        ];
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $width = $this->getSetting('avatar_width');
    $height = $this->getSetting('avatar_height');
    $chars = $this->getSetting('avatar_chars');

    $use_svg = $this->getSetting('avatar_svg');
    $summary[] = $this->t('Avatar: @widthpx x @heightpx, @chars characters, SVG: @svg',
      [
        '@width' => $width,
        '@height' => $height,
        '@chars' => $chars,
        '@svg' => $use_svg ? 'yes' : 'no',
      ]);

    return $summary;
  }

  /**
   * Get the config array to construct an Avatar instance.
   *
   * @return array
   *   The config array.
   */
  private function getAvatarConfig() {
    $width = $this->getSetting('avatar_width');
    $height = $this->getSetting('avatar_height');
    $font_family = $this->getSetting('avatar_font_family');
    $backgrounds = $this->getSetting('avatar_backgrounds');
    $font_size = $this->getSetting('avatar_font_size');
    $uppercase = $this->getSetting('avatar_uppercase');
    $chars = $this->getSetting('avatar_chars');

    return [
      'chars' => $chars,
      'fontFamily' => $font_family,
      'uppercase' => $uppercase,
      'fontSize' => $font_size,
      'backgrounds' => array_map('trim', explode("\n", $backgrounds)),
      'width' => $width,
      'height' => $height,
    ];
  }

}
