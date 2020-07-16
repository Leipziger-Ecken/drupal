<?php

declare(strict_types = 1);

namespace Drupal\date_recur_modular\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\date_recur\DateRecurHelper;
use Drupal\date_recur\DateRecurRuleInterface;
use Drupal\date_recur_modular\DateRecurModularUtilityTrait;
use Drupal\date_recur_modular\DateRecurModularWidgetFieldsTrait;
use Drupal\date_recur_modular\Plugin\Field\FieldWidget\DateRecurModularSierraWidget;
use RRule\RSet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modified for Leipziger Ecken project (https://github.com/Leipziger-Ecken/drupal):
 * 
 * * Advanced stylings, i18n
 */

/**
 * Generate a form to excluding occurrences, designed for display in modal.
 */
class DateRecurModularSierraModalOccurrencesForm extends FormBase {

  use DateRecurModularWidgetFieldsTrait;
  use DateRecurModularUtilityTrait;

  /**
   * Date format for exclusion dates.
   */
  protected const UTC_FORMAT = 'Ymd\THis\Z';

  /**
   * The PrivateTempStore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new DateRecurModularSierraModalOccurrencesForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The PrivateTempStore factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PrivateTempStoreFactory $tempStoreFactory, DateFormatterInterface $dateFormatter) {
    $this->configFactory = $configFactory;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('tempstore.private'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_recur_modular_sierra_occurrences_modal';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'date_recur_modular/sierra_modal_occurrences_form';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#theme'] = 'date_recur_modular_sierra_widget_modal_occurrences_form';

    $collection = $this->tempStoreFactory->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE);
    /** @var string|null $rrule */
    $rrule = $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_KEY);
    /** @var string $dateFormat */
    $dateFormatId = $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_DATE_FORMAT);

    $multiplier = $form_state->get('occurrence_multiplier');
    if (!isset($multiplier)) {
      $form_state->set('occurrence_multiplier', 1);
      $multiplier = 1;
    }

    $form['original_string'] = [
      '#type' => 'value',
      '#value' => $rrule,
    ];

    $dtStartString = $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_DTSTART);

    if (!empty($dtStartString)) {
      $dtStart = \DateTime::createFromFormat(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_DTSTART_FORMAT, $dtStartString);
    }
    else {
      // Use current date if there is no valid starting date from handoff.
      $dtStart = new \DateTime();
    }
    $form['date_start'] = [
      '#type' => 'value',
      '#value' => $dtStart,
    ];

    if (isset($rrule)) {
      try {
        $helper = DateRecurHelper::create($rrule, $dtStart);
      }
      catch (\Exception $e) {
      }
    }

    // Rebuild using Rset because we want to be able to iterate over occurrences
    // without considering any existing EXDATEs.
    $rset = new RSet();
    /** @var \DateTime[] $excluded */
    $excludes = [];
    if (isset($helper)) {
      foreach ($helper->getRules() as $rule) {
        $rset->addRRule($rule->getParts());
      }
      $excludes = $helper->getExcluded();
    }
    sort($excludes);

    // Initial limit is 1024, with 128 per page thereafter, with an absolute
    // maximum of 64000. Limit prevents performance issues and abuse.
    $limit = min(1024 + (128 * ($multiplier - 1)), 64000);
    // 6 months from now plus 4 months thereafter.
    $limitDate = (new \DateTime())
      ->modify(sprintf('+%d months', 6 + (($multiplier - 1) * 4)));
    $occurrences = [];
    $matchedExcludes = [];
    $unmatchedExcludes = [];
    $iteration = 0;
    foreach ($rset as $occurrenceDate) {
      if ($iteration > $limit || $limitDate < $occurrenceDate) {
        break;
      }

      $occurrences[$iteration] = [
        'date' => $occurrenceDate,
        'excluded' => FALSE,
      ];

      // After each occurrence evaluate if there were any excludes that fit
      // between this occurrence and last occurrence.
      foreach ($excludes as $k => $exDate) {
        if ($exDate < $occurrenceDate) {
          // Occurrence was between this and last occurrence, so likely no
          // longer matches against the RRULE.
          // Its done progessively like this instead of comparing occurrences to
          // EXDATEs as some EXDATEs may fall outside of the date/count limits.
          $unmatchedExcludes[] = $exDate;
          unset($excludes[$k]);
        }
        elseif ($exDate == $occurrenceDate) {
          // Occurrence matches an exclude date exactly.
          $matchedExcludes[] = $exDate;
          $occurrences[$iteration]['excluded'] = TRUE;
          unset($excludes[$k]);
        }
      }
      $iteration++;
    }

    /** @var \DateTime[] $outOfLimitExcludes */
    $outOfLimitExcludes = $excludes;
    unset($excludes);

    // Any remaining excludes are out of range. We dont know if they are matched
    // to the rule or not. Keep them and save.
    $form['excludes_out_of_limit'] = [
      '#type' => 'value',
      '#value' => $outOfLimitExcludes,
    ];

    if (false && count($unmatchedExcludes)) {
      // Shows "x ungültige Wiederholungen" which occurs when reloading this
      // form multiple times per active edit session.
      // Use only for debugging OR implement wisely ("This action removes x Wiederholungen")
      $form['invalid_excludes'] = [
        '#type' => 'details',
        '#title' => $this->formatPlural(count($unmatchedExcludes), '@count invalid exclude', '@count invalid excludes'),
      ];
      $form['invalid_excludes']['help'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{{ message }}</p>',
        '#context' => [
          'message' => $this->formatPlural(count($unmatchedExcludes), 'This invalid excluded occurrence will be automatically removed.', 'These invalid excluded occurrences will be automatically removed.'),
        ],
      ];
      $form['invalid_excludes']['table'] = [
        '#type' => 'table',
        '#header' => [
          'date' => $this->t('Date'),
        ],
      ];
      $form['invalid_excludes']['table']['#rows'] = array_map(function (\DateTime $date) use ($dateFormatId, $dtStart): array {
        return [
          'date' => $this->dateFormatter->format($date->getTimestamp(), $dateFormatId, '', $dtStart->getTimezone()->getName()),
        ];
      }, $unmatchedExcludes);
    }

    $form['occurrences'] = [
      '#type' => 'details',
      '#title' => $this->t('Occurrences'),
      '#open' => TRUE,
    ];

    $form['occurrences']['help'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ message }}</p>',
      '#context' => [
        'message' => $this->t('This table shows a selection of occurrences. Occurrences may be removed individually. Times are displayed in <em>@time_zone</em> time zone.', [
          '@time_zone' => $dtStart->getTimezone()->getName(),
        ]),
      ],
    ];

    $form['occurrences']['table'] = [
      '#type' => 'table',
      '#header' => [
        'exclude' => $this->t('Exclude'),
        'date' => $this->t('Date'),
      ],
      '#empty' => $this->t('There are no occurrences.'),
      '#prefix' => '<div id="occurrences-table">',
      '#suffix' => '</div>',
    ];

    $i = 0;
    foreach ($occurrences as $occurrence) {
      /** @var \DateTime $date */
      /** @var bool $excluded */
      ['date' => $date, 'excluded' => $excluded] = $occurrence;
      $date->setTimezone($dtStart->getTimezone());
      $row = [];
      $row['exclude'] = [
        '#type' => 'checkbox',
        '#return_value' => $i,
        '#default_value' => $excluded,
        '#ajax' => [
          'event' => 'change',
          // Need 'url' and 'options' for this submission button to use this
          // controller not the caller.
          'url' => Url::fromRoute('date_recur_modular_widget.sierra_modal_occurrences_form'),
          'options' => [
            'query' => [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
          ],
          'callback' => [$this, 'ajaxSubmitForm'],
        ],
      ];
      $row['date']['#markup'] = $this->dateFormatter->format($date->getTimestamp(), $dateFormatId, '', $dtStart->getTimezone()->getName());
      $row['#date_object'] = $date;
      $form['occurrences']['table'][$i] = $row;
      $i++;
    }

    /*$form['occurrences']['show_more'] = [
      '#type' => 'container',
    ];
    $form['occurrences']['show_more']['count_message'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ count_message }}</p>',
      '#context' => [
        'count_message' => $this->formatPlural(count($outOfLimitExcludes), 'There is @count more hidden excluded occurrence.', 'There are @count more hidden excluded occurrences.'),
      ],
    ];
    if (count($outOfLimitExcludes) > 0) {
      $nextExclude = reset($outOfLimitExcludes);
      $form['occurrences']['show_more']['next_message'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{{ next_message }}</p>',
        '#context' => [
          'next_message' => $this->t('Next hidden excluded occurrence is at @date', [
            '@date' => $this->dateFormatter->format($nextExclude->getTimestamp(), $dateFormatId, '', $dtStart->getTimezone()->getName()),
          ]),
        ],
      ];
    }
    $form['occurrences']['show_more']['show_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show more'),
      '#ajax' => [
        'event' => 'click',
        // Need 'url' and 'options' for this submission button to use this
        // controller not the caller.
        'url' => Url::fromRoute('date_recur_modular_widget.sierra_modal_occurrences_form'),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
        'callback' => [$this, 'ajaxShowMore'],
      ],
    ];
*/
    /**$form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Done'),
      '#button_type' => 'primary',
      '#ajax' => [
        'event' => 'click',
        // Need 'url' and 'options' for this submission button to use this
        // controller not the caller.
        'url' => Url::fromRoute('date_recur_modular_widget.sierra_modal_occurrences_form'),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
        'callback' => [$this, 'ajaxSubmitForm'],
      ],
    ];**/

    return $form;
  }

  /**
   * Callback to reload modal with more occurrences.
   */
 /* public function ajaxShowMore(array &$form, FormStateInterface $form_state): AjaxResponse {
    $form_state->setRebuild();

    $multiplier = $form_state->get('occurrence_multiplier');
    $form_state->set('occurrence_multiplier', $multiplier + 1);

    $response = new AjaxResponse();
    $form = \Drupal::formBuilder()->rebuildForm($this->getFormId(), $form_state, $form);
    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Occurrences'),
      $form,
      ['width' => '575']
    ));
    return $response;
  }*/

  /**
   * Callback to submit modal modified exclusions.
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

    $originalString = $form_state->getValue('original_string');
    /** @var \DateTime $dtStart */
    $dtStart = $form_state->getValue('date_start');

    try {
      $helper = DateRecurHelper::create($originalString, $dtStart);
    }
    catch (\Exception $e) {
    }

    // Rebuild original set without EXDATES.
    $rset = new RSet();
    if (isset($helper)) {
      array_walk($helper->getRules(), function (DateRecurRuleInterface $rule) use ($rset) {
        $parts = $rule->getParts();
        unset($parts['DTSTART']);
        $rset->addRRule($parts);
      });
    }

    // Add checked excluded dates.
    foreach ($form_state->getValue('table') as $i => $row) {
      if ($row['exclude'] !== 0) {
        $date = $form['occurrences']['table'][$i]['#date_object'];
        $rset->addExDate($date);
      }
    }
    // Add out of range excluded dates.
    foreach ($form_state->getValue('excludes_out_of_limit') as $exDate) {
      /** @var \DateTime $exDate */
      $rset->addExDate($exDate);
    }

    $lines = [];
    foreach ($rset->getRRules() as $rule) {
      /** @var \RRule\RRule $rule */
      $lines[] = 'RRULE:' . $rule->rfcString(FALSE);
    }

    $utc = new \DateTimeZone('UTC');
    $exDates = array_map(function (\DateTime $exDate) use ($utc) {
      $exDate->setTimezone($utc);
      return $exDate->format(static::UTC_FORMAT);
    }, $rset->getExDates());
    if (count($exDates) > 0) {
      $lines[] = 'EXDATE:' . implode(',', $exDates);
    }

    $collection = $this->tempStoreFactory->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE);
    $collection->set(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_KEY, implode("\n", $lines));

    $refreshBtnName = sprintf('[name="%s"]', $collection->get(DateRecurModularSierraWidget::COLLECTION_MODAL_STATE_REFRESH_BUTTON));
    $response
      ->addCommand(new CloseDialogCommand())
      // Transfers new lines to widget.
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
