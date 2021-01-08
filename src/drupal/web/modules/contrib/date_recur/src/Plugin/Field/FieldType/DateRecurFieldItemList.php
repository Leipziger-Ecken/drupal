<?php

declare(strict_types = 1);

namespace Drupal\date_recur\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemList;
use Drupal\date_recur\DateRecurPartGrid;
use Drupal\date_recur\Event\DateRecurEvents;
use Drupal\date_recur\Event\DateRecurValueEvent;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Recurring date field item list.
 */
class DateRecurFieldItemList extends DateRangeFieldItemList {

  /**
   * An event dispatcher, primarily for unit testing purposes.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  protected $eventDispatcher = NULL;

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    parent::postSave($update);
    $event = new DateRecurValueEvent($this, !$update);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_VALUE_SAVE, $event);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(): void {
    parent::delete();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_ENTITY_DELETE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision(): void {
    parent::deleteRevision();
    $event = new DateRecurValueEvent($this, FALSE);
    $this->getDispatcher()->dispatch(DateRecurEvents::FIELD_REVISION_DELETE, $event);
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected function getDispatcher(): EventDispatcherInterface {
    if (isset($this->eventDispatcher)) {
      return $this->eventDispatcher;
    }
    return \Drupal::service('event_dispatcher');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state): array {
    $element = parent::defaultValuesForm($form, $form_state);

    $defaultValue = $this->getFieldDefinition()->getDefaultValueLiteral();

    $element['default_date_time_zone'] = [
      '#type' => 'select',
      '#title' => $this->t('Start and end date time zone'),
      '#description' => $this->t('Time zone is required if a default start date or end date is provided.'),
      '#options' => $this->getTimeZoneOptions(),
      '#default_value' => $defaultValue[0]['default_date_time_zone'] ?? '',
      '#states' => [
        // Show the field if either start or end is set.
        'invisible' => [
          [
            ':input[name="default_value_input[default_date_type]"]' => ['value' => ''],
            ':input[name="default_value_input[default_end_date_type]"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    $element['default_time_zone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#description' => $this->t('Default time zone.'),
      '#options' => $this->getTimeZoneOptions(),
      '#default_value' => $defaultValue[0]['default_time_zone'] ?? '',
      '#empty_option' => $this->t('- Current user time zone -'),
    ];

    $element['default_rrule'] = [
      '#type' => 'textarea',
      '#title' => $this->t('RRULE'),
      '#default_value' => $defaultValue[0]['default_rrule'] ?? '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state): void {
    $defaultDateTimeZone = $form_state->getValue(['default_value_input', 'default_date_time_zone']);
    if (empty($defaultDateTimeZone)) {
      $defaultStartType = $form_state->getValue(['default_value_input', 'default_date_type']);
      if (!empty($defaultStartType)) {
        $form_state->setErrorByName('default_value_input][default_date_time_zone', (string) $this->t('Time zone must be provided if a default start date is provided.'));
      }

      $defaultEndType = $form_state->getValue(['default_value_input', 'default_end_date_type']);
      if (!empty($defaultEndType)) {
        $form_state->setErrorByName('default_value_input][default_date_time_zone', (string) $this->t('Time zone must be provided if a default end date is provided.'));
      }
    }

    parent::defaultValuesFormValidate($element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state): array {
    $values = parent::defaultValuesFormSubmit($element, $form, $form_state);

    $rrule = $form_state->getValue(['default_value_input', 'default_rrule']);
    if ($rrule) {
      $values[0]['default_rrule'] = $rrule;
    }

    $defaultDateTimeZone = $form_state->getValue(['default_value_input', 'default_date_time_zone']);
    if ($defaultDateTimeZone) {
      $values[0]['default_date_time_zone'] = $defaultDateTimeZone;
    }

    $defaultTimeZone = $form_state->getValue(['default_value_input', 'default_time_zone']);
    if ($defaultTimeZone) {
      $values[0]['default_time_zone'] = $defaultTimeZone;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition): array {
    assert(is_array($default_value));
    $defaultDateTimeZone = $default_value[0]['default_date_time_zone'] ?? NULL;

    $defaultValue = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    $defaultValues = [[]];

    $hasDefaultStartDateType = !empty($defaultValue[0]['default_date_type']);
    $hasDefaultEndDateType = !empty($defaultValue[0]['default_end_date_type']);
    if (!empty($defaultDateTimeZone) && ($hasDefaultStartDateType || $hasDefaultEndDateType)) {
      $storageFormat = $definition->getSetting('datetime_type') == DateRecurItem::DATETIME_TYPE_DATE ? DateRecurItem::DATE_STORAGE_FORMAT : DateRecurItem::DATETIME_STORAGE_FORMAT;

      // Instruct 'value' and 'end_value' to convert from the localised time
      // zone to UTC.
      $formatSettings = ['timezone' => DateTimeItemInterface::STORAGE_TIMEZONE];

      if ($hasDefaultStartDateType) {
        $start_date = new DrupalDateTime($defaultValue[0]['default_date'], $defaultDateTimeZone);
        $start_value = $start_date->format($storageFormat, $formatSettings);
        $defaultValues[0]['value'] = $start_value;
        $defaultValues[0]['start_date'] = $start_date;
      }

      if ($hasDefaultEndDateType) {
        $end_date = new DrupalDateTime($defaultValue[0]['default_end_date'], $defaultDateTimeZone);
        $end_value = $end_date->format($storageFormat, $formatSettings);
        $defaultValues[0]['end_value'] = $end_value;
        $defaultValues[0]['end_date'] = $end_date;
      }

      $defaultValue = $defaultValues;
    }

    $rrule = $default_value[0]['default_rrule'] ?? NULL;
    if (!empty($rrule)) {
      $defaultValue[0]['rrule'] = $rrule;
    }

    $defaultTimeZone = $default_value[0]['default_time_zone'] ?? NULL;
    if (!empty($defaultTimeZone)) {
      $defaultValue[0]['timezone'] = $defaultTimeZone;
    }
    else {
      $timeZone = \date_default_timezone_get();
      if (empty($timeZone)) {
        throw new \Exception('Something went wrong. User has no time zone.');
      }
      $defaultValue[0]['timezone'] = $timeZone;
    }

    unset($defaultValue[0]["default_time_zone"]);
    unset($defaultValue[0]["default_rrule"]);
    return $defaultValue;
  }

  /**
   * Get a list of time zones suitable for a select field.
   *
   * @return array
   *   A list of time zones where keys are PHP time zone codes, and values are
   *   human readable and translatable labels.
   */
  protected function getTimeZoneOptions() {
    return \system_time_zones(TRUE, TRUE);
  }

  /**
   * Set the event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Get the parts grid for this field.
   *
   * @return \Drupal\date_recur\DateRecurPartGrid
   *   The parts grid for this field.
   */
  public function getPartGrid(): DateRecurPartGrid {
    $partSettings = $this->getFieldDefinition()->getSetting('parts');
    // Existing field configs may not have a parts setting yet.
    $partSettings = $partSettings ?? [];
    return DateRecurPartGrid::configSettingsToGrid($partSettings);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($delta) {
    foreach ($this->list as $item) {
      assert($item instanceof DateRecurItem);
      $item->resetHelper();
    }
    parent::onChange($delta);
  }

}
