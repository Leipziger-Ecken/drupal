<?php

namespace Drupal\le_webbuilder;

use Drupal\views\Views;

/**
 * Twig extension with some useful functions and filters.
 *
 * Dependencies are not injected for performance reason.
 */
class TwigExtension extends \Twig_Extension
{

  /**
   * {@inheritdoc}
   */
  public function getFunctions()
  {
    $context_options = ['needs_context' => TRUE];
    $all_options = ['needs_environment' => TRUE, 'needs_context' => TRUE];
    return [
      new \Twig_SimpleFunction('color_hex_to_hsl', [$this, 'colorHexToHsl']),
      new \Twig_SimpleFunction('color_rgb_to_hsl', [$this, 'colorRgbToHsl']),
      new \Twig_SimpleFunction('color_hex_to_rgb', [$this, 'colorHexToRgb']),
      new \Twig_SimpleFunction('webbuilder_url', [$this, 'webbuilderUrl']),
      new \Twig_SimpleFunction('webbuilder_akteur_id', [$this, 'webbuilderAkteurId']),
      new \Twig_SimpleFunction('webbuilder_view', [$this, 'webbuilderView']),
      new \Twig_SimpleFunction('akteur_webbuilder_url', [$this, 'akteurWebbuilderUrl']),
    ];
  }

  public function getFilters()
  {
    return [

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'le_webbuilder';
  }

  public function colorHexToRgb($hex)
  {
    return sscanf($hex, '#%02x%02x%02x');
  }

  public function colorRgbToHsl($r, $g, $b)
  {
    // normalize to values between 0..1
    $r = $r / 255;
    $g = $g / 255;
    $b = $b / 255;

    // Determine lowest & highest value and chroma
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $chroma = $max - $min;

    // Calculate Luminosity
    $l = ($max + $min) / 2;

    // If chroma is 0, the given color is grey
    // therefore hue and saturation are set to 0
    if ($chroma == 0)
    {
        $h = 0;
        $s = 0;
    }

    // Else calculate hue and saturation.
    // Check http://en.wikipedia.org/wiki/HSL_and_HSV for details
    else
    {
        switch($max) {
            case $r:
                $h_ = fmod((($g - $b) / $chroma), 6);
                if($h_ < 0) $h_ = (6 - fmod(abs($h_), 6)); // Bugfix: fmod() returns wrong values for negative numbers
                break;

            case $g:
                $h_ = ($b - $r) / $chroma + 2;
                break;

            case $b:
                $h_ = ($r - $g) / $chroma + 4;
                break;
            default:
                break;
        }

        $h = $h_ / 6;
        $s = 1 - abs(2 * $l - 1);
    }

    // Return HSL Color as array
    return [$h * 360, $s * 100, $l * 100];
  }

  public function colorHexToHsl($hex)
  {
    $rgb = $this->colorHexToRgb($hex);
    return $this->colorRgbToHsl($rgb[0], $rgb[1], $rgb[2]);
  }

  protected function getNodeById($node_id)
  {
    return \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
  }

  public function webbuilderUrl($webbuilder_id, $url = '<front>')
  {
    $webbuilder = $this->getNodeById($webbuilder_id);
    if ($url === '<front>') {
      if (isset($webbuilder->field_frontpage[0])) {
        $frontpage_id = $webbuilder->field_frontpage[0]->target_id;
        $frontpage = $this->getNodeById($frontpage_id);
        if ($frontpage) {
          return $frontpage->toUrl()->toString();
        }
      }
    }

    return null;
  }

  public function akteurWebbuilderUrl($akteur_id, $url = '<front>')
  {
    $result = \Drupal::entityQuery('node')
    ->condition('type', 'webbuilder')
    ->condition('og_audience', $akteur_id)
    ->condition('status', 1)
    ->range(0, 1)
    ->execute();
    if (!count($result)) {
      return null;
    }

    $webbuilder_id = array_values($result)[0];

    return $this->webbuilderUrl($webbuilder_id, $url);
  }

  public function webbuilderAkteurId($webbuilder_id)
  {
    $webbuilder = $this->getNodeById($webbuilder_id);

    if (isset($webbuilder->og_audience[0])) {
      return $webbuilder->og_audience[0]->target_id;
    }

    return null;
  }

  public function webbuilderView($view_name, $display_name, array $options = [], array $arguments = [])
  {
    $options = array_merge([
      'pagination' => false,
      'filters' => false,
      'images' => false,
      'per_page' => null,
      'layout' => null,
    ], $options);

    $arguments[] = $options['layout'];
    $arguments[] = $options['filters'];
    $arguments[] = $options['images'];

    $view = Views::getView($view_name);
    $view->setDisplay($display_name);

    if ($options['per_page']) {
      $view->setItemsPerPage(intval($options['per_page']));
    }
    if (!$options['pagination']) {
      $view->pager = null;
    }
    $view->setArguments($arguments);

    return $view->render();
  }
}
