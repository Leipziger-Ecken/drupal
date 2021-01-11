<?php

namespace Drupal\htmlmail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\htmlmail\Helper\HtmlMailHelper;

/**
 * Class HtmlMailConfigurationForm.
 *
 * @package Drupal\htmlmail\Form
 */
class HtmlMailConfigurationForm extends ConfigFormBase {

  protected $moduleHandler;

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'htmlmail.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'htmlmail_general';
  }

  /**
   * Retrieves the filter format list.
   *
   * @return array
   *   An array with all filter formats from current user.
   */
  protected function getFilterFormatsList() {
    $formats = ['0' => $this->t('Unfiltered')];

    $filter_formats = filter_formats($this->currentUser());
    foreach ($filter_formats as $id => $format) {
      $formats[$id] = $format->label();
    }

    return $formats;
  }

  /**
   * Defines the settings form for HTML Mail.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('htmlmail.settings');

    $form['template'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Step 1'),
    ];

    $form['template']['htmlmail_template'] = [
      '#type' => 'details',
      '#prefix' => '<strong>' . $this->t('Template file:') . '</strong><br />'
      . $this->t('A template file is applied to your message header, subject, and body text.  You may copy the <code><a href=":uri">:template</a></code> file to your default theme directory and use it to customize your messages.',
          [
            ':uri' => 'http://cgit.drupalcode.org/htmlmail/plain/htmlmail.html.twig?id=refs/heads/8.x-1.x',
            ':template' => 'htmlmail.html.twig',
          ]
      ),
      '#title' => $this->t('Instructions'),
      '#open' => FALSE,
    ];

    $form['template']['htmlmail_template']['instructions'] = [
      '#type' => 'item',
      '#suffix' => $this->t(':Instructions
        <p>When formatting an email message with a given <code>$module</code> and <code>$key</code>, <a href="https://www.drupal.org/project/htmlmail">HTML Mail</a> will use the first template file it finds from the following list:</p>
        <ol style="list-style-type: decimal;">
          <li><code>htmlmail--$module--$key.html.twig</code></li>
          <li><code>htmlmail--$module.html.twig</code></li>
          <li><code>htmlmail.html.twig</code></li>
        </ol>
        <p>For each filename, <a href="https://www.drupal.org/project/htmlmail">HTML Mail</a> looks first in the chosen <em>Email theme</em> directory, then in its own module directory, before proceeding to the next filename.</p>
        <p>For example, if <code>example_module</code> sends mail with:</p>
        <pre>
          <code>\Drupal::service(\'plugin.manager.mail\')->mail("example_module", "outgoing_message" ...)</code>
        </pre>
        <p>the possible template file names would be:</p>
        <ol style="list-style-type: decimal;">
          <li><code>htmlmail--example_module--outgoing_message.html.twig</code></li>
          <li><code>htmlmail--example_module.html.twig</code></li>
          <li><code>htmlmail.html.twig</code></li>
        </ol>
        <p>Template files are cached, so remember to clear the cache by visiting <u>admin/config/development/performance</u> after changing any <code>.html.twig</code> files.</p>
        <p>The following variables available in this template:</p>
        <dl>
        <dt><strong><code>message.body</code></strong></dt>
        <dd>
          <p>The message body text.</p>
        </dd>
        <dt><strong><code>message.module</code></strong></dt>
        <dd>
          <p>The first argument to <a href="https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailManager.php/function/MailManager%3A%3Amail/8.6.x"><code>MailManager::mail</code></a>, which is, by convention, the machine-readable name of the sending module.</p>
        </dd>
        <dt><strong><code>message.key</code></strong></dt>
        <dd>
          <p>The second argument to <a href="https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailManager.php/function/MailManager%3A%3Amail/8.6.x"><code>MailManager::mail</code></a>, which should give some indication of why this email is being sent.</p>
        </dd>
        <dt><strong><code>message.message_id</code></strong></dt>
        <dd>
          <p>The email message id, which should be equal to <code>"{$module}_{$key}"</code>.</p>
        </dd>
        <dt><strong><code>message.headers</code></strong></dt>
        <dd>
          <p>An array of email <code>(name =&gt; value)</code> pairs.</p>
        </dd>
        <dt><strong><code>message.from</code></strong></dt>
        <dd>
          <p>The configured sender address.</p>
        </dd>
        <dt><strong><code>message.to</code></strong></dt>
        <dd>
          <p>The recipient email address.</p>
        </dd>
        <dt><strong><code>message.subject</code></strong></dt>
        <dd>
          <p>The message subject line.</p>
        </dd>
        <dt><strong><code>message.body</code></strong></dt>
        <dd>
          <p>The formatted message body.</p>
        </dd>
        <dt><strong><code>message.language</code></strong></dt>
        <dd>
          <p>The language code for this message.</p>
        </dd>
        <dt><strong><code>message.params</code></strong></dt>
        <dd>
          <p>Any module-specific parameters.</p>
        </dd>
        <dt><strong><code>template_name</code></strong></dt>
        <dd>
          <p>The basename of the active template.</p>
        </dd>
        <dt><strong><code>template_path</code></strong></dt>
        <dd>
          <p>The relative path to the template directory.</p>
        </dd>
        <dt><strong><code>template_url</code></strong></dt>
        <dd>
          <p>The absolute URL to the template directory.</p>
        </dd>
        <dt><strong><code>theme</code></strong></dt>
        <dd>
          <p>The name of the <em>Email theme</em> used to hold template files. If the <a href="https://www.drupal.org/project/echo">Echo</a> module is enabled this theme will also be used to transform the message body into a fully-themed webpage.</p>
        </dd>
        <dt><strong><code>theme_path</code></strong></dt>
        <dd>
          <p>The relative path to the selected <em>Email theme</em> directory.</p>
        </dd>
        <dt><strong><code>theme_url</code></strong></dt>
        <dd>
          <p>The absolute URL to the selected <em>Email theme</em> directory.</p>
        </dd>
        <dt><strong><code>debug</code></strong></dt>
        <dd>
          <p><code>TRUE</code> to add some useful debugging info to the bottom of the message.</p>
        </dd>
        </dl>
        <p>Other modules may also add or modify theme variables by implementing a <code>MODULENAME_preprocess_htmlmail(&amp;$variables)</code> <a href="https://api.drupal.org/api/drupal/modules--system--theme.api.php/function/hook_preprocess_HOOK/8.6.x">hook function</a>.</p>',
      [':Instructions' => '']
      ),
    ];

    $form['template']['htmlmail_debug'] = [
      '#type' => 'checkbox',
      '#prefix' => '<br />',
      '#title' => '<em>' . $this->t('(Optional)') . '</em> ' . $this->t('Debug'),
      '#default_value' => $config->get('htmlmail_debug'),
      '#description' => $this->t('Add debugging info (Set <code>$debug</code> to <code>TRUE</code>).'),
    ];

    $form['theme'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Step 2'),
    ];

    $form['theme']['htmlmail_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Email theme'),
      '#default_value' => $config->get('htmlmail_theme'),
      '#options' => HtmlMailHelper::getAllowedThemes(),
      '#suffix' => '<p>'
      . $this->t('Choose the theme that will hold your customized templates from Step 1 above.')
      . '</p><p>'
      . ($this->moduleHandler->moduleExists('echo') ?
          $this->t('The templated text will be styled by your chosen theme.  This lets you use any one of <a href=":themes">over 800</a> themes to style your messages.  Creating an email-specific sub-theme lets you use the full power of the <a href=":theme_system">drupal theme system</a> to format your messages.',
            [
              ':themes' => 'https://www.drupal.org/project/project_theme',
              ':theme_system' => 'https://www.drupal.org/documentation/theme',
            ]
          ) :
          $this->t('If you install and enable the <a href=":echo">Echo</a> module, the theme you select will also be used to style your messages as if they were pages on your website.',
            [
              ':echo' => 'https://www.drupal.org/project/echo',
            ]
          )
      )
      . '</p>',
    ];

    $form['filter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Step 3'),
    ];

    $form['filter']['htmlmail_use_mime_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the Mime Mail class (PEAR).'),
      '#default_value' => $config->get('htmlmail_use_mime_mail'),
      '#description' => $this->t('Use the Mime Mail external class to send HTML Mail. Remember to download the external class.'),
    ];

    $form['filter']['htmlmail_html_with_plain'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide simple plain/text alternative of the HTML mail.'),
      '#default_value' => $config->get('htmlmail_html_with_plain'),
      '#description' => $this->t('This may increase the quality of your outgoing emails for the spam filters.'),
      '#states' => [
        'visible' => [
          ':input[name="htmlmail_use_mime_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['filter']['htmlmail_postfilter'] = [
      '#type' => 'select',
      '#title' => $this->t('Post-filtering'),
      '#default_value' => $config->get('htmlmail_postfilter'),
      '#options' => $this->getFilterFormatsList(),
      '#suffix' => '<p>'
      . $this->t('You may choose a <a href=":formats">text format</a> to be used for filtering email messages <em>after</em> theming.  This allows you to use any combination of <a href=":filters">over 200 filter modules</a> to make final changes to your message before sending.',
          [
            ':formats' => 'admin/config/content/formats',
            ':filters' => 'https://www.drupal.org/project/modules/?filters=type%3Aproject_project%20tid%3A63%20hash%3A1hbejm%20-bs_project_sandbox%3A1%20bs_project_has_releases%3A1',
          ]
      )
      . '</p><p>'
      . $this->t('Here is a recommended configuration:')
      . '</p><ul><li><dl><dt>'
      . $this->t('<a href=":emogrifier">Emogrifier</a>',
          [':emogrifier' => 'https://www.drupal.org/project/emogrifier']
      )
      . '</dt><dd>'
      . $this->t('Converts stylesheets to inline style rules for consistent display on mobile devices and webmail.')
      . '</dd></dl></li><li><dl><dt>'
      . $this->t('<a href=":transliteration">Transliteration</a>',
          [':transliteration' => 'https://www.drupal.org/project/transliteration']
      )
      . '</dt><dd>'
      . $this->t('Converts non-ASCII text to US-ASCII equivalents. This helps prevent Microsoft <q>smart-quotes</q> from appearing as question-marks in Mozilla Thunderbird.'
      )
      . '</dd></dl></li><li><dl><dt>'
      . $this->t('<a href=":pathologic">Pathologic</a>',
          [':pathologic' => 'https://www.drupal.org/project/pathologic']
      )
      . '</dt><dd>'
      . $this->t('Converts relative URLS to absolute URLS so that clickable links in your message will work as intended.')
      . '</dd></dl></ul>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('htmlmail_use_mime_mail')) {
      // Try including the files, then cleck for the classes.
      @include_once 'Edu/Mail/mime.php';
      @include_once 'Edu/Mail/mimeDecode.php';
      @include_once 'Edu/Mail/mimePart.php';
      if (!class_exists('Mail_Mime')
        || !class_exists('Mail_mimeDecode')
        || !class_exists('Mail_mimePart')
      ) {

        $form_state->setErrorByName('htmlmail_use_mime_mail', $this->t('The Mail MIME class was not found. Please download the required class using the <a href="@help">help section</a> commands or disable the option.',
          ['@help' => '/admin/help/htmlmail']
        ));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('htmlmail.settings')
      // Set the submitted configuration setting.
      ->set('htmlmail_debug', $form_state->getValue('htmlmail_debug'))
      ->set('htmlmail_theme', $form_state->getValue('htmlmail_theme'))
      ->set('htmlmail_html_with_plain', $form_state->getValue('htmlmail_html_with_plain'))
      ->set('htmlmail_postfilter', $form_state->getValue('htmlmail_postfilter'))
      ->set('htmlmail_use_mime_mail', $form_state->getValue('htmlmail_use_mime_mail'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * HtmlMailConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service, injected into constructor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler service, injected into constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

}
