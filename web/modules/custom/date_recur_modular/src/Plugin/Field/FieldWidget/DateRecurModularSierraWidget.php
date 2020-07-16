<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\date_recur\DateRecurHelper;
use Drupal\date_recur\Entity\DateRecurInterpreterInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\date_recur_modular\Form\DateRecurModularSierraModalForm;
use Drupal\date_recur_modular\Form\DateRecurModularSierraModalOccurrencesForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modified for Leipziger Ecken project (https://github.com/Leipziger-Ecken/drupal):
 * 
 * * Show SierraOccurrencesForm directly inside form on page load, not as modal nor via btnClick
 * * Reload list of estimated, deactivatable occurrences after date-/time-input changes
 * * Removed some buttons and labels from the default SierraWidget form
 */

/**
 * Date recur sierra widget.
 *
 * This is a widget built with Drupal states, AJAX, modals, and interpreters.
 * It is inspired by the 2019 implementation of Google Calendar/Outlook for web
 * repeating-rule modal. It provides a single or multi-day all-day option.
 *
 * @FieldWidget(
 *   id = "date_recur_modular_sierra",
 *   label = @Translation("Modular: Sierra"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurModularSierraWidget extends DateRecurModularWidgetBase {

  use DateRecurModularWidgetFieldsTrait;
  use DependencyTrait;

  // @todo settings: allow all day.

  /**
   * Name of a private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE = 'date_recur_modular_sierra_modal_state';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_KEY = 'rrule';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_DTSTART = 'dtstart';

  /**
   * Name of a key in private tempstore collection.
   */
  public const COLLECTION_MODAL_STATE_REFRESH_BUTTON = 'refresh_button_name';

  /**
   * DTSTART format for COLLECTION_MODAL_STATE_DTSTART.
   */
  public const COLLECTION_MODAL_STATE_DTSTART_FORMAT = 'Y-m-d\TH:i:s e';

  /**
   * Stores field and delta.
   */
  public const COLLECTION_MODAL_STATE_PATH = 'field_and_delta';

  /**
   * Stores date format to use for occurrences.
   */
  public const COLLECTION_MODAL_DATE_FORMAT = 'date_format';

  /**
   * Form state key.
   */
  protected const FORM_STATE_RRULE_KEY = 'date_recur_modular_sierra_rrule';

  protected const MODE_ONCE = 'daily';

  protected const MODE_WEEKLY = 'weekly';

  protected const MODE_MONTHLY = 'monthly';

  protected const MODE_YEARLY = 'yearly';

  protected const HTML_TIME_FORMAT = 'H:i:s';

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Provides form building and processing.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Part grid for this list.
   *
   * Temporary storage for massage values.
   *
   * @var \Drupal\date_recur\DateRecurPartGrid
   */
  protected $partGrid;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date recur interpreter entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateRecurInterpreterStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'interpreter' => NULL,
      'date_format_type' => 'medium',
      'occurrences_modal' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $interpreter = $this->getInterpreter();
    $summary[] = $interpreter ?
      $this->t('Interpreter: @label', [
        '@label' => $interpreter->label() ?? $this->t('- Missing label -'),
      ]) :
      $this->t('No interpreter');

    if ($this->isOccurrencesModalEnabled()) {
      $dateFormatId = $this->getSetting('date_format_type');
      $dateFormat = $this->dateFormatStorage->load($dateFormatId);
      $summary[] = $dateFormat
        ? $this->t('Occurrence date format: @label', [
          '@label' => $dateFormat->label() ?? $dateFormat->id(),
        ])
        : $this->t('Occurrence date format: missing date format');
    }

    $summary[] = $this->isOccurrencesModalEnabled()
      ? $this->t('Occurrences button is enabled')
      : $this->t('Occurrences button is disabled');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $interpreterOptions = array_map(function (DateRecurInterpreterInterface $interpreter): string {
      return $interpreter->label() ?? (string) $this->t('- Missing label -');
    }, $this->dateRecurInterpreterStorage->loadMultiple());
    $form['interpreter'] = [
      '#type' => 'select',
      '#title' => $this->t('Recurring date interpreter'),
      '#description' => $this->t('Choose a plugin for converting rules into a human readable description.'),
      '#default_value' => $this->getSetting('interpreter'),
      '#options' => $interpreterOptions,
      '#required' => FALSE,
      '#empty_option' => $this->t('- Do not show interpreted rule -'),
    ];

    $dateFormatOptions = array_map(function (DateFormatInterface $dateFormat) {
      $time = new DrupalDateTime();
      $format = $this->dateFormatter->format($time->getTimestamp(), $dateFormat->id());
      return $dateFormat->label() . ' (' . $format . ')';
    }, $this->dateFormatStorage->loadMultiple());

    $form['date_format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Occurrence date format'),
      '#description' => $this->t('Date format type to display occurrences and excluded occurrences.'),
      '#options' => $dateFormatOptions,
      '#default_value' => $this->getSetting('date_format_type'),
    ];

    $form['occurrences_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Whether to enable occurrences button'),
      '#default_value' => $this->isOccurrencesModalEnabled(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $this->dependencies = parent::calculateDependencies();
    $interpreter = $this->getInterpreter();
    if ($interpreter) {
      $this->addDependency('config', $interpreter->getConfigDependencyName());
    }

    $id = $this->getSetting('date_format_type');
    $dateFormat = $this->dateFormatStorage->load($id);
    if ($dateFormat) {
      $this->addDependency('config', $dateFormat->getConfigDependencyName());
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    $settings = $this->getSettings();

    foreach ($dependencies['config'] ?? [] as $configDependency) {
      // Delete interpreter in settings if its being deleted.
      if ($configDependency instanceof DateRecurInterpreterInterface) {
        if ($settings['interpreter'] === $configDependency->id()) {
          unset($settings['interpreter']);
          $this->setSettings($settings);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

  /**
   * Constructs a new DateRecurModularSierraWidget.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The PrivateTempStore factory.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Provides form building and processing.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $configFactory, PrivateTempStoreFactory $tempStoreFactory, FormBuilderInterface $formBuilder, AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, DateFormatterInterface $dateFormatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $configFactory);
    $this->tempStoreFactory = $tempStoreFactory;
    $this->formBuilder = $formBuilder;
    $this->currentUser = $currentUser;
    $this->dateRecurInterpreterStorage = $entityTypeManager->getStorage('date_recur_interpreter');
    $this->dateFormatStorage = $entityTypeManager->getStorage('date_format');
    $this->languageManager = $languageManager;
    $this->dateFormatter = $dateFormatter;
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
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('tempstore.private'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList|\Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem[] $items */
    $elementParents = [$this->fieldDefinition->getName(), $delta];
    $element['#element_validate'][] = [$this, 'validateModularWidget'];
    $element['#theme'] = 'date_recur_modular_sierra_widget';
    $element['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $item = $items[$delta];

    $dropdownWrapper = 'dropdown-wrapper-' . implode('-', $elementParents);

    $element['buttons']['#tree'] = TRUE;
    $element['buttons']['custom_recurrences'] = [
      '#type' => 'button',
      '#value' => $this->t('Recurring rules'),
      '#ajax' => [
        'callback' => [$this, 'openTheModal'],
        'event' => 'click',
        'progress' => 'fullscreen',
      ],
      '#attributes' => [
        'class' => [
          'js-hide',
          'date-recur-modular-sierra-widget-recurrence-open',
        ],
      ],
      '#limit_validation_errors' => [],
      '#name' => Html::cleanCssIdentifier(implode('-', array_merge($elementParents, ['custom_recurrences']))),
    ];

    $element['buttons']['reload_recurrence_dropdown_custom'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reload dropdown and set to custom'),
      '#ajax' => [
        'callback' => [get_class($this), 'reloadRecurrenceDropdownCallback'],
        'event' => 'click',
        'progress' => 'fullscreen',
        'wrapper' => $dropdownWrapper,
      ],
      '#attributes' => [
        'class' => [
          'js-hide',
        ],
        'data-dialog-type' => 'modal',
      ],
      '#submit' => [[$this, 'transferModalToFormStateCallback']],
      '#limit_validation_errors' => [],
      // Needs a name so triggering element works on multicardinal elements.
      '#name' => Html::cleanCssIdentifier(implode('-', array_merge($elementParents, ['reload_recurrence_dropdown_custom']))),
    ];

    $element['buttons']['reload_recurrence_dropdown'] = [
      '#type' => 'button',
      '#value' => $this->t('Reload dropdown'),
      '#ajax' => [
        'callback' => [get_class($this), 'reloadRecurrenceDropdownCallback'],
        'event' => 'click',
        'progress' => 'fullscreen',
        'wrapper' => $dropdownWrapper,
      ],
      '#attributes' => [
        'class' => [
          'js-hide',
          'date-recur-modular-sierra-widget-reload-recurrence-options',
        ],
      ],
      '#limit_validation_errors' => [],
      // Needs a name so triggering element works on multicardinal elements.
      '#name' => Html::cleanCssIdentifier(implode('-', array_merge($elementParents, ['reload_recurrence_dropdown']))),
    ];

    $timeZone = $this->getDefaultTimeZone($item);
    $startDateInput = NestedArray::getValue($form_state->getUserInput(), array_merge($elementParents, ['day_start']));
    if ($startDateInput) {
      $startDate = \DateTime::createFromFormat('Y-m-d', $startDateInput, new \DateTimeZone($timeZone));
      if ($startDate) {
        $startTimeInput = NestedArray::getValue($form_state->getUserInput(), array_merge($elementParents, ['time_start']));
        if (is_string($startTimeInput)) {
          $timeObject = $this->parseTimeInput($startTimeInput);
          if ($timeObject) {
            $timeExploded = explode(':', $timeObject->format('H:i:s'));
            $startDate->setTime((int) $timeExploded[0], (int) $timeExploded[1], (int) $timeExploded[2]);
          }
          else {
            $startDate->setTime(0, 0, 0);
          }
        }
      }
    }
    elseif ($item->start_date instanceof DrupalDateTime) {
      $startDate = $item->start_date->getPhpDateTime();
    }
    if (!isset($startDate) || $startDate === FALSE) {
      $startDate = new \DateTime();
    }

    $rrule = $item->rrule ?? '';
    $element['field_path'] = [
      '#type' => 'value',
      '#value' => implode('/', [$this->fieldDefinition->getName(), $delta]),
    ];
    $element['rrule_in_storage'] = [
      '#type' => 'value',
      '#value' => $rrule,
    ];
    $recurrenceOption = !empty($rrule) ? $this->guessRecurrenceOptionFromRrule($startDate, $rrule) : NULL;

    /** @var string|null $interpretation */
    $interpretation = NULL;
    $customRrule = $form_state->get([static::FORM_STATE_RRULE_KEY, $element['field_path']['#value']]);
    if ((!empty($rrule) && $recurrenceOption === 'custom') || !empty($customRrule)) {
      $customRrule = empty($customRrule) ? $rrule : $customRrule;
      $helper = DateRecurHelper::create($customRrule, $startDate);
      $rules = $helper->getRules();

      /** @var \Drupal\date_recur\Plugin\DateRecurInterpreterPluginInterface $plugin */
      $interpreter = $this->getInterpreter();
      if ($interpreter) {
        $plugin = $interpreter->getPlugin();
        $language = $this->languageManager->getCurrentLanguage()->getId();
        $interpretation = $plugin->interpret($rules, $language, new \DateTimeZone($timeZone));
      }
      else {
        $interpretation = (string) $this->t('Custom: - Missing interpreter -');
      }
    }
    // Map for onBlurInputField AJAX/UI procedure
    $ajaxRefreshOccurrences = [
      'event' => 'blur', // @todo a delayed 'change' would be great! 
      'callback' => [$this, 'openOccurrencesModal'],
      'wrapper' => 'open-occurrences-wrapper', // div attached via #suffix at end of form, see below
      'method' => 'html', // @see https://api.drupal.org/api/drupal/core!core.api.php/group/ajax/8.2.x
      'disable-refocus' => true,
      'progress' => 'none',
    ];

    $element['day_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Start day'),
      '#title_display' => 'invisible',
      '#default_value' => $item->start_date instanceof DrupalDateTime ? $item->start_date->format('Y-m-d') : NULL,
      '#attributes' => [
        'type' => 'date',
        'class' => ['date-recur-modular-sierra-widget-start-date'],
      ],
      '#date_date_format' => 'Y-m-d',
      '#ajax' => $ajaxRefreshOccurrences,
    ];

    $element['day_end'] = [
      '#type' => 'date',
      '#title' => $this->t('End day'),
      '#title_display' => 'invisible',
      '#default_value' => $item->end_date instanceof DrupalDateTime ? $item->end_date->format('Y-m-d') : NULL,
      '#attributes' => [
        'type' => 'date',
        'class' => ['date-recur-modular-sierra-widget-start-end'],
      ],
      '#date_date_format' => 'Y-m-d',
      '#ajax' => $ajaxRefreshOccurrences, // @todo really required?
    ];

    $isAllDayName = $this->getName($element, ['is_all_day']);

    $element['time_start'] = [
      '#type' => 'date',
      '#attributes' => [
        'type' => 'time',
        // Must specify increment else browsers default to 60, which omits
        // seconds. Our validation expects seconds.
        'step' => 1,
      ],
      '#title' => $this->t('Time'),
      '#title_display' => 'invisible',
      '#default_value' => $item->start_date instanceof DrupalDateTime ? $item->start_date->format(static::HTML_TIME_FORMAT) : NULL,
      // Must specify increment else browsers default to 60, which omits
      // seconds. Our validation expects seconds.
      '#date_increment' => 1,
      '#ajax' => $ajaxRefreshOccurrences,
    ];
    $element['time_start']['#states']['visible'][0]['input[name="' . $isAllDayName . '"]'] = ['checked' => FALSE];

    $element['time_end'] = [
      '#title' => $this->t('Ending time'),
      '#title_display' => 'invisible',
      '#type' => 'date',
      '#attributes' => [
        'type' => 'time',
        // Must specify increment else browsers default to 60, which omits
        // seconds. Our validation expects seconds.
        'step' => 1,
      ],
      '#default_value' => $item->end_date instanceof DrupalDateTime ? $item->end_date->format(static::HTML_TIME_FORMAT) : NULL,
      '#ajax' => $ajaxRefreshOccurrences, // @todo really required?
    ];
    $element['time_end']['#states']['visible'][0]['input[name="' . $isAllDayName . '"]'] = ['checked' => FALSE];

    $element['is_all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All day'),
      '#default_value' => $this->isAllDay($item),
      '#ajax' => $ajaxRefreshOccurrences,
    ];

    $element['recurrence_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Recurrence option'),
      '#title_display' => 'invisible',
      '#default_value' => $recurrenceOption,
      '#empty_option' => $this->t('Does not repeat'),
      '#prefix' => '<div id="' . $dropdownWrapper . '">',
      '#attributes' => [
        'class' => ['date-recur-modular-sierra-widget-recurrence-option'],
      ],
      '#suffix' => '</div>',
      '#ajax' => $ajaxRefreshOccurrences,
    ];

    $element['recurrence_option']['#options'] = $this->getRecurrenceOptions($startDate);
    if (isset($interpretation)) {
      $element['recurrence_option']['#options']['custom'] = $interpretation;
    }
    $element['recurrence_option']['#options']['custom_open'] = $this->t('Custom...');

    $element['occurrences'] = [
      '#type' => 'inline_template',
      '#template' => 
      "<div id=\"open-occurrences-wrapper\">
        <div class=\"ajax-progress ajax-progress-throbber\">
          <div class=\"throbber\">&nbsp;</div>
          <div class=\"message\">". $this->t('Generiere Termine') ."...</div>
        </div>
      </div>",
    ];

    /*$element['occurrences'] = [
      '#type' => 'button',
      '#value' => $this->t('Show/exclude occurrences'),
      '#ajax' => [
        'callback' => [$this, 'openOccurrencesModal'],
        'event' => 'click',
        'progress' => 'fullscreen',
      ],
      '#attributes' => [
        'class' => [
          'date-recur-modular-sierra-widget-occurrences-open',
        ],
      ],
      '#limit_validation_errors' => [],
      // Needs a unique name as formbuilder cant differentiate between deltas.
      '#name' => Html::cleanCssIdentifier(implode('-', array_merge($elementParents, ['occurrences']))),
      '#access' => $this->isOccurrencesModalEnabled(),
      '#suffix' => 
      "<div id=\"open-occurrences-wrapper\">
        <div class=\"ajax-progress ajax-progress-throbber\">
          <div class=\"throbber\">&nbsp;</div>
          <div class=\"message\">". $this->t('Generiere Termine') ."...</div>
        </div>
      </div>",
    ];*/

    $element['time_zone'] = $this->getFieldTimeZone($timeZone);
    $element['time_zone']['#access'] = FALSE;

    return $element;
  }

  /**
   * Validates the widget.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateModularWidget(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $valueParents = $element['#parents'];
    $formParents = $element['#array_parents'];

    // Dont start validation until at least the start date is not empty.
    /** @var string|null $start */
    $startDay = $form_state->getValue(array_merge($valueParents, ['day_start']));
    if (empty($startDay)) {
      return;
    }

    /** @var string|null $timeZone */
    $timeZone = $form_state->getValue(array_merge($valueParents, ['time_zone']));
    if (empty($startDay)) {
      $form_state->setError($element, \t('Time zone must be set if start date is set.'));
    }

    $isAllDay = (bool) $form_state->getValue(array_merge($valueParents, ['is_all_day']));
    if ($isAllDay) {
      $form_state->setValue(array_merge($valueParents, ['time_start']), '00:00:00');
      $form_state->setValue(array_merge($valueParents, ['time_end']), '23:59:59');
    }

    try {
      $startDate = static::buildDatesFromFields(array_merge($formParents, ['day_start']), array_merge($formParents, ['time_start']), $timeZone, $form_state);
      $form_state->setValue(array_merge($valueParents, ['start']), $startDate);
    }
    catch (\Exception $e) {
      $message = \t('Start date and time invalid.');
      $form_state->setError($element['day_start'], $message);
      $form_state->setError($element['time_start'], $message);
    }

    try {
      $dateEnd = static::buildDatesFromFields(array_merge($formParents, ['day_end']), array_merge($formParents, ['time_end']), $timeZone, $form_state);
      $form_state->setValue(array_merge($valueParents, ['end']), $dateEnd);
    }
    catch (\Exception $e) {
      $message = \t('End date and time invalid.');
      $form_state->setError($element['day_end'], $message);
      $form_state->setError($element['time_end'], $message);
    }

    if (isset($startDate) && isset($dateEnd) && ($startDate > $dateEnd)) {
      $form_state->setError($element['day_end'], \t('End date cannot be before the start date.'));
    }
    elseif (isset($startDate) && !isset($dateEnd)) {
      $form_state->setError($element['day_end'], \t('End date must be set if start date is set.'));
    }
    elseif (!isset($startDate) && isset($dateEnd)) {
      $form_state->setError($element['day_start'], \t('Start date must be set if end date is set.'));
    }

    $rrule = $this->transferModalToFormStateCallback($complete_form, $form_state, true) ?? '';

    // Process RRULE.
    if (empty($rrule) && isset($startDate)) {
      $recurrenceOption = $form_state->getValue(array_merge($valueParents, ['recurrence_option']));
      if ($recurrenceOption === 'custom') {
        $rrule = $form_state->get([static::FORM_STATE_RRULE_KEY, $element['field_path']['#value']]);
        // There wont be a value in form state if the modal wasn't interacted
        // with, so fall back to value in storage.
        if (!isset($rrule)) {
          $rrule = $form_state->getValue(array_merge($valueParents, ['rrule_in_storage']));
        }
      }
      else {
        $rrule = static::buildRruleFromRecurrenceOption($startDate, $recurrenceOption);
      }
    }
    $form_state->setValue(array_merge($valueParents, ['rrule']), $rrule);
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurFieldItemList $items */
    $this->partGrid = $items->getPartGrid();
    parent::extractFormValues(...func_get_args());
    unset($this->partGrid);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $dateStorageFormat = $this->fieldDefinition->getSetting('datetime_type') == DateRecurItem::DATETIME_TYPE_DATE ? DateRecurItem::DATE_STORAGE_FORMAT : DateRecurItem::DATETIME_STORAGE_FORMAT;
    $dateStorageTimeZone = new \DateTimezone(DateRecurItem::STORAGE_TIMEZONE);

    $returnValues = [];
    foreach ($values as $delta => $value) {
      $returnValues[$delta] = [];
      $item = [];

      if (!empty($value['start'])) {
        $item['value'] = (clone $value['start'])
          ->setTimezone($dateStorageTimeZone)
          ->format($dateStorageFormat);
      }

      if (!empty($value['end'])) {
        $item['end_value'] = (clone $value['end'])
          ->setTimezone($dateStorageTimeZone)
          ->format($dateStorageFormat);
      }

      // If no start or end date then skip.
      if (count($item) === 0) {
        continue;
      }

      assert(strlen($value['time_zone']) > 0);
      $item['timezone'] = $value['time_zone'];

      if (!empty($value['rrule'])) {
        $item['rrule'] = $value['rrule'];
      }

      $returnValues[$delta] = $item;
    }
    return $returnValues;
  }

  /**
   * Callback to convert RRULE data from form to modal then open modal.
   */
  public function openTheModal(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $this->transferStateToTempstore($element, $form_state);

    // Open modal.
    $content = $this->formBuilder->getForm(DateRecurModularSierraModalForm::class);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $dialogOptions = ['width' => '575'];
    return (new AjaxResponse())
      ->setAttachments($content['#attached'])
      ->addCommand(new OpenModalDialogCommand($this->t('Custom recurrence'), $content, $dialogOptions));
  }

  /**
   * Callback to convert RRULE data from form to modal then open modal.
   */
  public function openOccurrencesModal(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $this->transferStateToTempstore($element, $form_state);

    // Open modal.
    $content = $this->formBuilder->getForm(DateRecurModularSierraModalOccurrencesForm::class);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $content;
    
    $dialogOptions = ['width' => '575'];
    return (new AjaxResponse())
      ->setAttachments($content['#attached'])
      ->addCommand(new OpenModalDialogCommand($this->t('Occurrences'), $content, $dialogOptions));
  }

  /**
   * Transfers element state to tempstore ready for modal to consume.
   *
   * @param array $element
   *   A single form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function transferStateToTempstore(array $element, FormStateInterface $form_state): void {
    $formParents = $element['#array_parents'];
    $valueParents = $element['#parents'];

    // Transfer RULE and Start Date to temporary storage.
    $timeZone = $form_state->getValue(array_merge($valueParents, ['time_zone']));
    try {
      $startDate = '';
      $startDate = static::buildDatesFromFields(array_merge($formParents, ['day_start']), array_merge($formParents, ['time_start']), $timeZone, $form_state);
    }
    catch (\Exception $e) {
    }
    $startDateStr = $startDate instanceof \DateTime ? $startDate->format(static::COLLECTION_MODAL_STATE_DTSTART_FORMAT) : '';
    $path = $form_state->getValue(array_merge($valueParents, ['field_path']));
    $rruleState = $form_state->get([static::FORM_STATE_RRULE_KEY, $path]) ?? $form_state->getValue(array_merge($valueParents, ['rrule_in_storage']));
    
    // @todo This option should be considered in OccurrencesForm
    // It actually DOES after clicking "benutzerdefiniert" and submitting o_O
    $recurrenceOption = $form_state->getValue(array_merge($valueParents, ['recurrence_option']));

    $collection = $this->tempStoreFactory->get(static::COLLECTION_MODAL_STATE);
    $collection->set(static::COLLECTION_MODAL_STATE_KEY, $rruleState);
    $collection->set(static::COLLECTION_MODAL_STATE_DTSTART, $startDateStr);
    $collection->set(static::COLLECTION_MODAL_STATE_PATH, $path);
    $collection->set(static::COLLECTION_MODAL_DATE_FORMAT, $this->getSetting('date_format_type'));
    $collection->set(static::COLLECTION_MODAL_STATE_REFRESH_BUTTON, $element['buttons']['reload_recurrence_dropdown_custom']['#name']);
  }

  /**
   * Callback to convert RRULE data from modal to be consumed by form.
   */
  public function transferModalToFormStateCallback(array &$form, FormStateInterface $form_state, bool $returnRuleOnly = false) {
    $collection = $this->tempStoreFactory->get(static::COLLECTION_MODAL_STATE);

    $fieldPath = $collection->get(static::COLLECTION_MODAL_STATE_PATH);
    if ($fieldPath) {
      $customRrule = $collection->get(static::COLLECTION_MODAL_STATE_KEY);
      if (isset($customRrule)) {
        $form_state->set([static::FORM_STATE_RRULE_KEY, $fieldPath], $customRrule);
        $collection->delete(static::COLLECTION_MODAL_STATE_KEY);
      }

      [$fieldName, $delta] = explode('/', $fieldPath);
      $input = &$form_state->getUserInput();
      // After closing modal, switch dropdown to custom setting.
      $input[$fieldName][$delta]['recurrence_option'] = 'custom';

      if ($returnRuleOnly) {
        return $customRrule;
      }
    }

    $form_state->setRebuild();
  }

  /**
   * Callback to reload contents of 'recurrence_option' element.
   */
  public function reloadRecurrenceDropdownCallback(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    return $element['recurrence_option'];
  }

  /**
   * Get recurrence options for a select element based on a start date.
   *
   * @param \DateTime $startDate
   *   A date to base recurrence options.
   *
   * @return array
   *   An array of option suitable for select element.
   */
  protected function getRecurrenceOptions(\DateTime $startDate): array {
    $dayOfMonth = $startDate->format('j');
    $tArgs = [
      '@weekday' => $startDate->format('l'),
      '@dayofmonth' => $dayOfMonth,
      '@month' => $startDate->format('F'),
    ];

    $monthWeekdayNth = static::getMonthWeekdayNth($startDate);
    $tArgs['@monthweekdaynth'] = $monthWeekdayNth;
    $tArgs['@monthweekdayordinal'] =
      ($monthWeekdayNth == 1 ? 'st' :
      ($monthWeekdayNth == 2 ? 'nd' :
      ($monthWeekdayNth == 3 ? 'rd' : 'th')));

    $options = [];
    $options['daily'] = $this->t('Daily');
    $options['weekly_oneday'] = $this->t('Weekly on @weekday', $tArgs);
    $options['monthly_th_weekday'] = $this->t('Monthly on the @monthweekdaynth@monthweekdayordinal @weekday', $tArgs);
    $options['yearly_monthday'] = $this->t('Annually on @month @dayofmonth', $tArgs);
    $options['weekdayly'] = $this->t('Every weekday (Monday to Friday)');
    return $options;
  }

  /**
   * Builds a RRULE string from a preset option given a particular start date.
   *
   * @param \DateTime $startDate
   *   A start date.
   * @param string $recurrenceOption
   *   A recurrence option.
   *
   * @return string
   *   A RRULE string.
   */
  public static function buildRruleFromRecurrenceOption(\DateTime $startDate, string $recurrenceOption): string {
    $weekdaysKeys = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $byDay = $weekdaysKeys[$startDate->format('w')];
    switch ($recurrenceOption) {
      case 'daily':
        return 'FREQ=DAILY';

      case 'weekly_oneday':
        return 'FREQ=WEEKLY;BYDAY=' . $byDay;

      case 'monthly_th_weekday':
        $monthWeekdayNth = static::getMonthWeekdayNth($startDate);
        return sprintf('FREQ=MONTHLY;BYDAY=%s;BYSETPOS=%s', $byDay, $monthWeekdayNth);

      case 'yearly_monthday':
        return sprintf('FREQ=YEARLY;BYMONTH=%s;BYMONTHDAY=%s', $startDate->format('n'), $startDate->format('j'));

      case 'weekdayly':
        return 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
    }

    return '';
  }

  /**
   * Attempt to guess a suitable recurrence option in getRecurrenceOptions.
   *
   * @param \DateTime $startDate
   *   A start date.
   * @param string $rrule
   *   A rule string.
   *
   * @return string|null
   *   An option, falls back to 'custom' if no suitable recurrence could be
   *   determined. If a field value is non recurring then this helper shouldn't
   *   be called.
   */
  protected function guessRecurrenceOptionFromRrule(\DateTime $startDate, string $rrule): ?string {
    try {
      $helper = DateRecurHelper::create($rrule, $startDate);
      /** @var \Drupal\date_recur\DateRecurRuleInterface[] $rules */
      $rules = $helper->getRules();
      $rule = reset($rules);
      if (!isset($rule)) {
        return 'custom';
      }
      // PATCH #3080488 https://www.drupal.org/files/issues/2019-09-19/until-exclude-custom_0.patch
      if (count($helper->getExcluded())) {
        return 'custom';
      }
      // ./PATCH
    }
    catch (\Exception $e) {
      return 'custom';
    }

    $parts = array_filter($rule->getParts());

    // PATCH #3080488 https://www.drupal.org/files/issues/2019-09-19/until-exclude-custom_0.patch
    if ($parts['UNTIL'] ?? FALSE) {
      return 'custom';
    }
    // ./PATCH

    $frequency = $rule->getFrequency();
    $interval = $parts['INTERVAL'] ?? 1;
    $count = $parts['COUNT'] ?? 1;
    $byParts = array_filter($parts, function ($value, $key): bool {
      return strpos($key, 'BY', 0) === 0;
    }, \ARRAY_FILTER_USE_BOTH);

    $byPartCount = count($byParts);
    $weekdaysKeys = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $byDay = explode(',', $byParts['BYDAY'] ?? '');
    $byDay = array_unique(array_intersect($weekdaysKeys, $byDay));
    $byDayStr = implode(',', $byDay);

    $byMonth = array_unique(explode(',', $byParts['BYMONTH'] ?? ''));
    sort($byMonth);
    $byMonthDay = array_unique(explode(',', $byParts['BYMONTHDAY'] ?? ''));
    sort($byMonthDay);
    $bySetPos = array_unique(explode(',', $byParts['BYSETPOS'] ?? ''));
    sort($bySetPos);

    $startDayWeekday = $weekdaysKeys[$startDate->format('w')];
    if ($interval == 1 && $count == 1) {
      if ($byPartCount === 0 && $frequency === 'DAILY') {
        return 'daily';
      }
      elseif ($frequency === 'WEEKLY' && $byDayStr === 'MO,TU,WE,TH,FR' && $byPartCount === 1) {
        return 'weekdayly';
      }
      elseif ($frequency === 'WEEKLY' && $byPartCount === 1 && count($byDay) === 1 && $byDayStr === $startDayWeekday) {
        // Only if weekday is same as start day.
        return 'weekly_oneday';
      }
      elseif ($frequency === 'MONTHLY' && $byPartCount === 2 && count($bySetPos) === 1 && count($byDay) === 1) {
        return 'monthly_th_weekday';
      }
      elseif ($frequency === 'YEARLY' && $byPartCount === 2 && count($byMonth) === 1 && count($byMonthDay) === 1) {
        return 'yearly_monthday';
      }
    }

    return 'custom';
  }

  /**
   * Load the interpreter to be used by this widget.
   *
   * @return \Drupal\date_recur\Entity\DateRecurInterpreterInterface|null
   *   An interpreter instance.
   */
  protected function getInterpreter(): ?DateRecurInterpreterInterface {
    $id = $this->getSetting('interpreter');
    if (!is_string($id) || empty($id)) {
      return NULL;
    }
    return $this->dateRecurInterpreterStorage->load($id);
  }

  /**
   * Determines whether occurrences modal is enabled.
   *
   * @return bool
   *   Whether occurrences modal is enabled.
   */
  protected function isOccurrencesModalEnabled(): bool {
    return !empty($this->getSetting('occurrences_modal'));
  }

  /**
   * Parses raw input from a time field.
   *
   * Inspired by \Drupal\Core\Datetime\Element\Datetime::valueCallback, exists
   * because plain 'time' fields do not have value callbacks.
   *
   * @param string $input
   *   Input from a time field.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A date object, or NULL if input was invalid. The date portion of this
   *   object should be ignored.
   */
  protected function parseTimeInput(string $input): ?DrupalDateTime {
    if (strlen($input) == 5) {
      $input .= ':00';
    }

    $timeFormat = DateFormat::load('html_time')->getPattern();
    try {
      return DrupalDateTime::createFromFormat($timeFormat, $input);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
