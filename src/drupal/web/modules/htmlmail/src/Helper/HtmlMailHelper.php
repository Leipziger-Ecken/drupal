<?php

namespace Drupal\htmlmail\Helper;

use Drupal\Component\Utility\Html;
use Drupal;

/**
 * Class HtmlMailHelper.
 *
 * @package Drupal\htmlmail\Helper
 */
class HtmlMailHelper {

  const HTMLMAIL_MODULE_NAME = 'htmlmail';
  const HTMLMAIL_USER_DATA_NAME = 'htmlmail_plaintext';

  /**
   * Returns an associative array of allowed themes.
   *
   * Based on code from the og_theme module.
   *
   * @return array
   *   The keys are the machine-readable names and the values are the .info file
   *   names.
   */
  public static function getAllowedThemes() {
    $allowed = &drupal_static(__FUNCTION__);

    if (!isset($allowed)) {
      $allowed = ['' => t('No theme')];
      $themes = \Drupal::service('theme_handler')->listInfo();
      uasort($themes, 'system_sort_modules_by_info_name');
      foreach ($themes as $key => $value) {
        if ($value->status) {
          $allowed[$key] = Html::escape($value->info['name']);
        }
      }
    }
    return $allowed;
  }

  /**
   * Retrieves the module name.
   *
   * @return string
   *   The module name used to store data on user.
   */
  public static function getModuleName() {
    return self::HTMLMAIL_MODULE_NAME;
  }

  /**
   * Retrieves the user data name.
   *
   * @return string
   *   The data field name stored on user data.
   */
  public static function getUserDataName() {
    return self::HTMLMAIL_USER_DATA_NAME;
  }

  /**
   * Returns the selected theme to use for outgoing emails.
   *
   * @param array $message
   *   (optional) The message to be themed.
   *
   * @return string
   *   The 'theme' key of $message if set and allowed, empty string otherwise.
   */
  public static function getSelectedTheme(array &$message = []) {
    $selected = isset($message['theme']) ? $message['theme'] : \Drupal::config('htmlmail.settings')->get('htmlmail_theme');
    if ($selected) {
      // Make sure the selected theme is allowed.
      $themes = self::getAllowedThemes();
      if (empty($themes[$selected])) {
        $selected = '';
      }
    }
    return $selected;
  }

  /**
   * Checks whether a given recipient email prefers plaintext-only messages.
   *
   * @param string $email
   *   The recipient email address.
   *
   * @return bool
   *   FALSE if the recipient prefers plaintext-only messages; otherwise TRUE.
   */
  public static function htmlMailIsAllowed($email) {
    return !($recipient = user_load_by_mail($email))
      || empty(\Drupal::service('user.data')->get(
        self::HTMLMAIL_MODULE_NAME,
        $recipient->id(),
        self::HTMLMAIL_USER_DATA_NAME
      ));
  }

  /**
   * Check if current user can see the option to receive only plain text mails.
   *
   * @return bool
   *   FALSE if user do not have permission to change or administer users.
   */
  public static function allowUserAccess() {
    $user = Drupal::currentUser();
    return ($user->hasPermission('choose htmlmail_plaintext') ||
      $user->hasPermission('administer users'));
  }

  /**
   * Retrieves the theme names based on module and key.
   *
   * @param array $message
   *   The message array with module name and key.
   *
   * @return array
   *   An array with themes name.
   */
  public static function getThemeNames(array $message) {
    $formatted_module = str_replace('_', '-', $message['module']);
    $formatted_key = str_replace('_', '-', $message['key']);

    return [
      'htmlmail__' . $formatted_module,
      'htmlmail__' . $formatted_module . '__' . $formatted_key,
      'htmlmail',
    ];
  }

}
