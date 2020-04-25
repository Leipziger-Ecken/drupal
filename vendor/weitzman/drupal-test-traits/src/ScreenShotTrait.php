<?php

namespace weitzman\DrupalTestTraits;

use Drupal\Component\Utility\Html;

/**
 * Adds methods to capture screenshots on failures.
 *
 * The environment variable `DTT_SCREENSHOT_REPORT_DIRECTORY` should contain
 * the destination directory for the captured screenshots. This can also be set
 * in `phpunit.xml`.
 *
 * @used-by \weitzman\DrupalTestTraits\ExistingSiteBase
 */
trait ScreenShotTrait
{

  /**
   * Captures and saves a screenshot.
   *
   * The filename generated screenshot will contain a unique ID, the URL where
   * the screenshot was taken and the given base filename.
   *
   * @param string $base_filename (optional)
   *   The base filename to use, defaults to 'screenshot'.
   */
    protected function captureScreenshot($base_filename = 'screenshot')
    {
        $directory = getenv('DTT_SCREENSHOT_REPORT_DIRECTORY') ?: '/sites/simpletest/browser_output';
        if ($directory) {
            // Ensure directory exists.
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            $current_url = Html::cleanCssIdentifier($this->getSession()->getCurrentUrl());
            $base_filename = Html::cleanCssIdentifier($base_filename);
            $filename = file_create_filename(uniqid() . '_' . $current_url . '_' . $base_filename . '.png', $directory);
            $screenshot = $this->getDriverInstance()->getScreenshot();
            file_put_contents($filename, $screenshot);
        }
    }
}
