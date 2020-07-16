<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\date_recur\DateRecurHelper;
use Drupal\date_recur_modular\DateRecurModularUtilityTrait;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\date_recur_modular\DateRecurModularWidgetOptions;
use Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularSierraWidget;
use RRule\RSet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate a form designed for display in modal.
 */
class DateRecurModularSierraModalForm extends FormBase {

  use DateRecurModularWidgetFieldsTrait;
  use DateRecurModularUtilityTrait;

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  protected const MODE_ONCE = 'daily';

  protected const MODE_WEEKLY = 'weekly';

  protected const MODE_MONTHLY = 'monthly';

  protected const MODE_YEARLY = 'yearly';

  protected const UTC_FORMAT = 'Ymd\THis\Z';

  /**
   * Constructs a new DateRecurModularSierraModalForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The PrivateTempStore factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PrivateTempStoreFactory $tempStoreFactory) {
    $this->configFactory = $configFactory;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_recur_modular_sierra_modal';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'date_recur_modular/date_recur_modular_sierra_widget_modal_form';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#theme'] = 'date_recur_modular_sierra_widget_modal_form';

    $collection = $this->tempStoreFactory
      ->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE);

    $rrule = $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_KEY);
    $form['original_string'] = [
      '#type' => 'value',
      '#value' => $rrule,
    ];

    $dtStartString = $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_DTSTART);

    if (!empty($dtStartString)) {
      $dtStart = \DateTime::createFromFormat(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_DTSTART_FORMAT, $dtStartString);
    }
    else {
      $dtStart = new \DateTime();
    }

    $parts = [];
    $rule1 = NULL;
    if (isset($rrule)) {
      $startDate = new \DateTime();
      try {
        $helper = DateRecurHelper::create($rrule, $startDate);
        $rules = $helper->getRules();
        $rule1 = count($rules) > 0 ? reset($rules) : NULL;
        $parts = $rule1 ? $rule1->getParts() : [];
      }
      catch (\Exception $e) {
      }
    }

    $form['basics'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $form['basics']['interval'] = [
      '#title' => $this->t('Repeat every'),
      '#type' => 'number',
      '#default_value' => $parts['INTERVAL'] ?? NULL,
      '#min' => 1,
    ];

    $form['basics']['freq'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#title_display' => 'invisible',
      '#options' => [
        static::MODE_ONCE => $this->t('day(s)'),
        static::MODE_WEEKLY => $this->t('week(s)'),
        static::MODE_MONTHLY => $this->t('month(s)'),
        static::MODE_YEARLY => $this->t('year(s)'),
      ],
      '#default_value' => $rule1 ? strtolower($rule1->getFrequency()) : NULL,
    ];

    $form['weekdays'] = $this->getFieldByDay($rule1);
    $form['weekdays']['#states']['visible'][] = [
      ':input[name="freq"]' => ['value' => static::MODE_WEEKLY],
    ];

    $dayOfMonth = (int) $dtStart->format('j');
    $tArgs = [
      '@weekday' => $dtStart->format('l'),
      '@dayofmonth' => $dayOfMonth,
    ];

    // Calculate which nth of weekday in the month. E.g 2nd Monday of the month.
    $monthWeekdayNth = static::getMonthWeekdayNth($dtStart);
    $tArgs['@monthweekdaynth'] = $monthWeekdayNth;
    $tArgs['@monthweekdayordinal'] =
      ($monthWeekdayNth === 1 ? 'st' :
      ($monthWeekdayNth === 2 ? 'nd' :
      ($monthWeekdayNth === 3 ? 'rd' : 'th')));

    $form['monthly_mode'] = [
      '#type' => 'select',
      '#options' => [
        'monthly_th' => $this->t('Monthly on day @dayofmonth', $tArgs),
        'monthly_th_weekday' => $this->t('Monthly on the @monthweekdaynth@monthweekdayordinal @weekday', $tArgs),
      ],
    ];
    $form['monthly_mode']['#states']['visible'][] = [
      ':input[name="freq"]' => ['value' => static::MODE_MONTHLY],
    ];

    $weekdaysKeys = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $monthlyParts = [
      'monthly_th' => [
        'BYMONTHDAY' => $dayOfMonth,
      ],
      'monthly_th_weekday' => [
        'BYDAY' => $weekdaysKeys[$dtStart->format('w')],
        'BYSETPOS' => $monthWeekdayNth,
      ],
    ];
    $form_state->setTemporaryValue('monthly_parts', $monthlyParts);

    $endsDate = NULL;
    try {
      $until = $parts['UNTIL'] ?? NULL;
      if (is_string($until)) {
        $endsDate = new \DateTime($until);
      }
      elseif ($until instanceof \DateTimeInterface) {
        $endsDate = $until;
      }
    }
    catch (\Exception $e) {
    }
    $count = $parts['COUNT'] ?? NULL;

    $form['ends'] = [
      '#type' => 'details',
      '#title' => $this->t('Ends'),
      '#open' => TRUE,
    ];
    $form['ends']['#theme'] = 'date_recur_modular_sierra_widget_modal_form_ends';

    $endsModeDefault =
      $endsDate ? DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE :
      ($count > 0 ? DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES : DateRecurModularWidgetOptions::ENDS_MODE_INFINITE);
    $form['ends']['ends_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Ends'),
      '#options' => [
        DateRecurModularWidgetOptions::ENDS_MODE_INFINITE => $this->t('Never'),
        DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE => $this->t('On'),
        DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES => $this->t('After'),
      ],
      '#default_value' => $endsModeDefault,
    ];

    $form['ends']['ends_count'] = [
      '#type' => 'number',
      '#title' => $this->t('End after number of occurrences'),
      '#title_display' => 'invisible',
      '#field_suffix' => $this->t('occurrences'),
      '#default_value' => $count ?? 1,
      '#min' => 1,
    ];
    $form['ends']['ends_count']['#states']['enabled'][] = [
      // This applies correctly but Drupal has no theming for disabled dates.
      ':input[name="ends_mode"]' => ['value' => DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES],
    ];

    // States dont yet work on date time so put it in a container.
    // @see https://www.drupal.org/project/drupal/issues/2419131
    $form['ends']['ends_date'] = [
      '#type' => 'container',
    ];
    $form['ends']['ends_date']['#states']['enabled'][] = [
      // This applies correctly but Drupal has no theming for disabled dates.
      ':input[name="ends_mode"]' => ['value' => DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE],
    ];
    $form['ends']['ends_date']['ends_date'] = [
      '#type' => 'datetime',
      '#default_value' => $endsDate ? DrupalDateTime::createFromDateTime($endsDate) : NULL,
      // Fix values tree thanks to state+container hack.
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Done'),
      '#button_type' => 'primary',
      '#ajax' => [
        'event' => 'click',
        // Need 'url' and 'options' for this submission button to use this
        // controller not the caller.
        'url' => Url::fromRoute('date_recur_modular_widget.sierra_modal_form'),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
        'callback' => [$this, 'ajaxSubmitForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $endsMode = $form_state->getValue('ends_mode');
    /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $endsDate */
    $endsDate = $form_state->getValue('ends_date');

    if ('date' === $endsMode && !$endsDate instanceof DrupalDateTime) {
      // Prevent submission, if for example only date provided (missing time).
      $form_state->setError($form['ends']['ends_date'], $this->t('Invalid date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      // Inspired by \Drupal\form_api_example\Form\ModalForm::ajaxSubmitForm.
      $form['status_messages'] = [
        '#type' => 'status_messages',
      ];
      // Open the form again as a modal.
      return $response->addCommand(new OpenModalDialogCommand(
        $this->t('Errors'),
        $form,
        ['width' => '575']
      ));
    }

    $frequency = $form_state->getValue('freq');

    $parts = [];
    $parts['FREQ'] = strtoupper($frequency);
    $parts['INTERVAL'] = $form_state->getValue('interval');
    if (static::MODE_WEEKLY === $frequency) {
      $weekDays = array_values(array_filter($form_state->getValue('weekdays')));
      $parts['BYDAY'] = implode(',', $weekDays);
    }

    if (static::MODE_MONTHLY === $frequency) {
      $monthlyMode = $form_state->getValue('monthly_mode');
      $monthlyParts = $form_state->getTemporaryValue(['monthly_parts', $monthlyMode]);
      $parts += $monthlyParts;
    }

    $endsMode = $form_state->getValue('ends_mode');
    /** @var \Drupal\Core\Datetime\DrupalDateTime|array|null $endsDate */
    $endsDate = $form_state->getValue('ends_date');

    // Ends mode.
    if ($endsMode === DateRecurModularWidgetOptions::ENDS_MODE_OCCURRENCES) {
      $parts['COUNT'] = (int) $form_state->getValue('ends_count');
    }
    elseif ($endsMode === DateRecurModularWidgetOptions::ENDS_MODE_ON_DATE && $endsDate instanceof DrupalDateTime) {
      $endsDateUtcAdjusted = (clone $endsDate)
        ->setTimezone(new \DateTimeZone('UTC'));
      $parts['UNTIL'] = $endsDateUtcAdjusted->format('Ymd\THis\Z');
    }

    // Build RRULE.
    $ruleKv = [];
    foreach ($parts as $k => $v) {
      $ruleKv[] = "$k=$v";
    }
    $ruleString = implode(';', $ruleKv);

    // Rset cannot be casted to string yet, rebuild it here, see also
    // https://github.com/rlanvin/php-rrule/issues/37
    $lines = [];
    $lines[] = 'RRULE:' . $ruleString;

    // Preserve non-RRULE components from original string.
    $originalString = $form_state->getValue('original_string');
    $rset = new RSet($originalString);
    $utc = new \DateTimeZone('UTC');

    $exDates = array_map(function (\DateTime $exDate) use ($utc) {
      $exDate->setTimezone($utc);
      return $exDate->format(static::UTC_FORMAT);
    }, $rset->getExDates());
    if (count($exDates) > 0) {
      $lines[] = 'EXDATE:' . implode(',', $exDates);
    }

    $collection = $this->tempStoreFactory
      ->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE);
    $collection->set(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_KEY, implode("\n", $lines));

    $refreshBtnName = sprintf('[name="%s"]', $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_REFRESH_BUTTON));
    $response
      ->addCommand(new CloseDialogCommand())
      ->addCommand(new InvokeCommand($refreshBtnName, 'trigger', ['click']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return new AjaxResponse();
  }

}
