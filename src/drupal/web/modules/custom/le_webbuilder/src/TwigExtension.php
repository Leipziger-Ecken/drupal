<?php

namespace Drupal\le_webbuilder;

use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * Twig extension with some useful functions and filters.
 *
 * Dependencies are not injected for performance reason.
 */
class TwigExtension extends \Twig_Extension
{

  protected static $webbuilderCache = [];
  protected static $webbuilderStylesCache = [];

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
      new \Twig_SimpleFunction('webbuilder_id', [$this, 'webbuilderId']),
      new \Twig_SimpleFunction('webbuilder_akteur_id', [$this, 'webbuilderAkteurId']),
      new \Twig_SimpleFunction('webbuilder_layout', [$this, 'webbuilderLayout']),
      new \Twig_SimpleFunction('webbuilder_view', [$this, 'webbuilderView']),
      new \Twig_SimpleFunction('webbuilder_layout_styles', [$this, 'webbuilderLayoutStyles']),
      new \Twig_SimpleFunction('akteur_webbuilder_url', [$this, 'akteurWebbuilderUrl']),
    ];
  }

  public function getFilters()
  {
    return [
      new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecode']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'le_webbuilder';
  }

  public function jsonDecode($value)
  {
    if (!is_string($value)) {
      return null;
    }

    try {
      return json_decode($value);
    } catch (\Exception $e) {
      return $e . '';
    }
  }

  public function colorHexToRgb($hex)
  {
    if (!$hex || !is_string($hex)) {
      return [0,0,0];
    }
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

  protected function getWebbuilderById($webbuilder_id)
  {
    if (!isset(self::$webbuilderCache[$webbuilder_id])) {
      $webbuilder = $this->getNodeById($webbuilder_id);
      self::$webbuilderCache[$webbuilder_id] = $webbuilder;
    }

    return self::$webbuilderCache[$webbuilder_id];
  }

  public function webbuilderUrl($webbuilder_id, string $url = '<front>', array $route_parameters = [])
  {
    $webbuilder = $this->getWebbuilderById($webbuilder_id);
    if (isset($route_parameters['destination'])) {
      $destination = $route_parameters['destination'];
      unset($route_parameters['destination']);
    } else {
      $destination = null;
    }
    if ($url === '<front>') {
      if (isset($webbuilder->field_frontpage[0])) {
        $frontpage_id = $webbuilder->field_frontpage[0]->target_id;
        $frontpage = $this->getNodeById($frontpage_id);
        if ($frontpage) {
          return $frontpage->toUrl()->toString();
        } else {
          return $webbuilder->toUrl()->toString();
        }
      } else {
        return $webbuilder->toUrl()->toString();
      }
    } else {
      $akteur_id = $webbuilder->og_audience[0]->target_id;
      return Url::fromRoute(
        $url,
        array_merge($route_parameters, ['akteur' => $akteur_id, 'webbuilder' => $webbuilder_id]),
        [
          'query' => ['destination' => $destination]
        ]
      )->toString();
    }

    return null;
  }

  public function akteurWebbuilderUrl($akteur_id, string $url = '<front>', array $route_parameters = [])
  {
    $result = \Drupal::entityQuery('node')
    ->condition('type', 'webbuilder')
    ->condition('og_audience', $akteur_id)
    ->condition('status', 1)
    ->sort('published_at', 'desc')
    ->range(0, 1)
    ->execute();
    if (!count($result)) {
      return null;
    }

    $webbuilder_id = array_values($result)[0];

    return $this->webbuilderUrl($webbuilder_id, $url, $route_parameters);
  }

  public function webbuilderId()
  {
    $current_url = Url::fromRoute('<current>')->toString();
    $parts = explode('/', trim($current_url, '/'));

    if (strpos($current_url, '/node/preview/') !== false) {
      if (count($parts) >= 3) {
        $uuid = $parts[2];
        $result = \Drupal::entityQuery('node')->condition('uuid', $uuid)->execute();
        if (count($result)) {
          $nid = array_values($result)[0];
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

          switch($node->getType()) {
            case 'webbuilder':
              return $nid;
            case 'webbuilder_page':
              return count($node->field_webbuilder->getValue()) ? $node->field_webbuilder->getValue()[0]['target_id'] : null;
            default:
              return null;
          }
        }
      }
    } elseif (strpos($current_url, '/node/') !== false) {
      if (count($parts) >= 2) {
        $nid = $parts[1];
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

        if ($node) {
          switch ($node->getType()) {
            case 'webbuilder':
              return $nid;
            case 'webbuilder_page':
              return count($node->field_webbuilder->getValue()) ? $node->field_webbuilder->getValue()[0]['target_id'] : null;
            default:
              return null;
          }
        }
      }
    } else {
      if (count($parts) >= 3) {
        return $parts[3];
      }
    }

    return null;
  }

  public function webbuilderAkteurId($webbuilder_id)
  {
    $webbuilder = $this->getWebbuilderById($webbuilder_id);

    if (isset($webbuilder->og_audience[0])) {
      return $webbuilder->og_audience[0]->target_id;
    }

    return null;
  }

  public function webbuilderLayout($webbuilder_id = null)
  {
    if (!$webbuilder_id) {
      $webbuilder_id = $this->webbuilderId();
    }

    $webbuilder = $this->getWebbuilderById($webbuilder_id);

    if (isset($webbuilder->field_layout) && isset($webbuilder->field_layout[0])) {
      return $webbuilder->field_layout[0]->value;
    }

    return 'default';
  }

  public function webbuilderView($view_name, $display_name, array $options = [], array $arguments = [])
  {
    $options = array_merge([
      'pagination' => false,
      'filters' => false,
      'images' => false,
      'per_page' => null,
      'layout' => null,
      'no_results_body' => null,
    ], $options);

    $arguments[] = json_encode($options);

    $view = Views::getView($view_name);
    $view->setDisplay($display_name);

    if ($options['per_page']) {
      $view->setItemsPerPage(intval($options['per_page']));
    }

    if (!$options['pagination']) {
      $view->pager = null;
    }
    $view->setArguments($arguments);
    $view->override_url = Url::fromUserInput(\Drupal::service('path.current')->getPath());
    $renderedView = $view->render();

    if (!$options['pagination']) {
      $renderedView['#view']->pager = null;
    }
    return $renderedView;
  }

  public function webbuilderLayoutStyles($layout = null)
  {
    if (!$layout) {
      $layout = $this->webbuilderLayout($this->webbuilderId());
    }

    if (isset(self::$webbuilderStylesCache[$layout])) {
      return self::$webbuilderStylesCache[$layout];
    }

    if ($layout !== 'default') {
      $defaulStyles = $this->webbuilderLayoutStyles('default');
    }

    $styles = [];
    $stylesPath = __DIR__ . '/../../../../themes/custom/leipzigerEckenWebbuilder/templates/layouts/' . $layout . '/styles.json';

    if (file_exists($stylesPath)) {
      try {
        $layoutStyles = json_decode(trim(file_get_contents($stylesPath)), true);

        if ($layout === 'default') {
          $styles = $layoutStyles !== null ? $layoutStyles : [];
        } else {
          // FIXME: merge recursive but do not append values if keys match
          $styles = $layoutStyles !== null ? array_replace_recursive([], $defaulStyles, $layoutStyles) : $defaulStyles;
        }
      } catch (\Exception $e) {
        $styles = $defaulStyles;
      }
    } else {
      $styles = $defaulStyles;
    }

    self::$webbuilderStylesCache[$layout] = $styles;

    return self::$webbuilderStylesCache[$layout];
  }
}
