<?php

namespace Drupal\le_webbuilder;

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
    return \Drupal::entityManager()->getStorage('node')->load($node_id);
  }

  public function webbuilderUrl($webbuilder_id, $url)
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
}
