 <?php

/**
 * @file
 * Enables site configuration for a leipziger_ecken site installation.
 */

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 * @thanks demo_umami.profile
 */
function leipziger_ecken_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Leipziger Ecken';
}
