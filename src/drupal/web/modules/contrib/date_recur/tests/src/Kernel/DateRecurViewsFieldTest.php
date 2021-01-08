<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur\DateRecurOccurrences;
use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the results of 'date_recur_date' field plugin.
 *
 * Tests field plugin for start and end date columns on field and occurrence
 * tables.
 *
 * @coversDefaultClass \Drupal\date_recur\Plugin\views\field\DateRecurDate
 *
 * @group date_recur
 */
class DateRecurViewsFieldTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'date_recur_entity_test',
    'date_recur_views_test',
    'entity_test',
    'datetime',
    'datetime_range',
    'date_recur',
    'field',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['dr_entity_test_list'];

  /**
   * Field mapping for testing.
   *
   * @var array
   */
  protected $map;

  /**
   * The entity type for testing.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Name of field for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp(FALSE);
    $this->installEntitySchema('dr_entity_test');
    ViewTestData::createTestViews(get_class($this), ['date_recur_views_test']);
    $this->map = ['id' => 'id'];

    // This is the name of the pre-installed base field.
    $this->fieldName = 'dr';
    $this->entityType = 'dr_entity_test';

    $user = User::create([
      'uid' => 2,
      'timezone' => 'Australia/Sydney',
    ]);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Tests field.
   */
  public function testField() {
    $entity1 = $this->createEntity();
    $entity1->{$this->fieldName} = [
      [
        // 9am-5pm.
        'value' => '2018-11-05T22:00:00',
        'end_value' => '2018-11-06T06:00:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity1->save();
    $entity2 = $this->createEntity();
    $entity2->{$this->fieldName} = [
      [
        // 9:05am-5:05pm.
        'value' => '2018-11-05T22:05:00',
        'end_value' => '2018-11-06T06:05:00',
        'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;COUNT=3',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity2->save();

    $definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($this->entityType);
    $baseTable = 'dr_entity_test';
    $occurrenceTableName = DateRecurOccurrences::getOccurrenceCacheStorageTableName($definitions[$this->fieldName]);

    $view = View::load('dr_entity_test_list');
    /** @var \Drupal\views\ViewExecutable $executable */
    $executable = $view->getExecutable();

    // Add the relationship.
    $viewsFieldName = $this->fieldName . '_occurrences';
    $executable->addHandler('default', 'relationship', $baseTable, $viewsFieldName, []);

    $options = [];
    $options['order'] = 'ASC';
    $viewsFieldName = $this->fieldName . '_value';
    $executable->addHandler('default', 'sort', $occurrenceTableName, $viewsFieldName, $options);

    $options = [];
    $options['date_format'] = 'long';
    $viewsFieldName = $this->fieldName . '_value';
    $startFieldId = $executable->addHandler('default', 'field', $occurrenceTableName, $viewsFieldName, $options);

    $options = [];
    $options['date_format'] = 'long';
    $viewsFieldName = $this->fieldName . '_end_value';
    $endFieldId = $executable->addHandler('default', 'field', $occurrenceTableName, $viewsFieldName, $options);

    $this->executeView($executable);

    // There are 3 occurrences for two entities.
    $this->assertCount(6, $executable->result);
    $expectedResult = [
      [
        'entity_id' => 1,
        'start' => '2018-11-05T22:00:00',
        'end' => '2018-11-06T06:00:00',
      ],
      [
        'entity_id' => 2,
        'start' => '2018-11-05T22:05:00',
        'end' => '2018-11-06T06:05:00',
      ],
      [
        'entity_id' => 1,
        'start' => '2018-11-06T22:00:00',
        'end' => '2018-11-07T06:00:00',
      ],
      [
        'entity_id' => 2,
        'start' => '2018-11-06T22:05:00',
        'end' => '2018-11-07T06:05:00',
      ],
      [
        'entity_id' => 1,
        'start' => '2018-11-07T22:00:00',
        'end' => '2018-11-08T06:00:00',
      ],
      [
        'entity_id' => 2,
        'start' => '2018-11-07T22:05:00',
        'end' => '2018-11-08T06:05:00',
      ],
    ];
    $this->map = [
      'id' => 'entity_id',
      $startFieldId => 'start',
      $endFieldId => 'end',
    ];
    $this->assertIdenticalResultset($executable, $expectedResult, $this->map);

    // Render the dates.
    $assertRendered = [
      [
        'start' => 'Tuesday, November 6, 2018 - 09:00',
        'end' => 'Tuesday, November 6, 2018 - 17:00',
      ],
      [
        'start' => 'Tuesday, November 6, 2018 - 09:05',
        'end' => 'Tuesday, November 6, 2018 - 17:05',
      ],
      [
        'start' => 'Wednesday, November 7, 2018 - 09:00',
        'end' => 'Wednesday, November 7, 2018 - 17:00',
      ],
      [
        'start' => 'Wednesday, November 7, 2018 - 09:05',
        'end' => 'Wednesday, November 7, 2018 - 17:05',
      ],
      [
        'start' => 'Thursday, November 8, 2018 - 09:00',
        'end' => 'Thursday, November 8, 2018 - 17:00',
      ],
      [
        'start' => 'Thursday, November 8, 2018 - 09:05',
        'end' => 'Thursday, November 8, 2018 - 17:05',
      ],
    ];
    $result = $executable->result;
    foreach ($assertRendered as $rowIndex => $assertRender) {
      $renderedStart = $executable->field[$startFieldId]->advancedRender($result[$rowIndex]);
      $this->assertEquals($assertRender['start'], $renderedStart, 'Asserting start for start: index' . $rowIndex);
      $renderedEnd = $executable->field[$endFieldId]->advancedRender($result[$rowIndex]);
      $this->assertEquals($assertRender['end'], $renderedEnd, 'Asserting start for end: index' . $rowIndex);
    }
  }

  /**
   * Creates an unsaved test entity.
   *
   * @return \Drupal\date_recur_entity_test\Entity\DrEntityTest
   *   A test entity.
   */
  protected function createEntity() {
    return DrEntityTest::create();
  }

}
