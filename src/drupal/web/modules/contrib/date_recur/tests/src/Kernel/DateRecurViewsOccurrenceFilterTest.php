<?php

namespace Drupal\Tests\date_recur\Kernel;

use Drupal\date_recur_entity_test\Entity\DrEntityTest;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the results of 'date_recur_occurrences_filter' filter plugin.
 *
 * Tests with a base field.
 *
 * @coversDefaultClass \Drupal\date_recur\Plugin\views\filter\DateRecurFilter
 *
 * @group date_recur
 */
class DateRecurViewsOccurrenceFilterTest extends ViewsKernelTestBase {

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

    $user = User::create([
      'uid' => 2,
      'timezone' => 'Australia/Sydney',
    ]);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Tests date recur filter plugin.
   */
  public function testDateRecurFilterAbsoluteYear() {
    // Testing around 2008.
    $entity1 = $this->createEntity();
    $entity1->{$this->fieldName} = [
      [
        // Before 2008.
        'value' => '2007-12-12T23:00:00',
        'end_value' => '2007-12-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity1->save();
    $entity2 = $this->createEntity();
    $entity2->{$this->fieldName} = [
      [
        // Intersecting start of 2008.
        'value' => '2007-12-12T23:00:00',
        'end_value' => '2008-01-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity2->save();
    $entity3 = $this->createEntity();
    $entity3->{$this->fieldName} = [
      [
        // Within 2008.
        'value' => '2008-02-12T23:00:00',
        'end_value' => '2008-02-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity3->save();
    $entity4 = $this->createEntity();
    $entity4->{$this->fieldName} = [
      [
        // Intersecting end of 2008.
        'value' => '2008-12-30T23:00:00',
        'end_value' => '2009-01-02T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity4->save();
    $entity5 = $this->createEntity();
    $entity5->{$this->fieldName} = [
      [
        // After 2008.
        'value' => '2009-01-02T23:00:00',
        'end_value' => '2009-01-03T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity5->save();
    $entity6 = $this->createEntity();
    $entity6->{$this->fieldName} = [
      [
        // Covering entirety of 2008.
        'value' => '2007-12-02T23:00:00',
        'end_value' => '2009-01-03T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity6->save();

    $exposedIdentifier = $this->fieldName . '_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      'value_granularity' => 'year',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => $this->fieldName . '_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    // Input values are in the users timezone.
    $this->assertFilter(
      [$exposedIdentifier => '2006'],
      [],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2007'],
      [
        ['id' => $entity1->id()],
        ['id' => $entity2->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2008'],
      [
        ['id' => $entity2->id()],
        ['id' => $entity3->id()],
        ['id' => $entity4->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2009'],
      [
        ['id' => $entity4->id()],
        ['id' => $entity5->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2010'],
      [],
      $filterOptions
    );
  }

  /**
   * Tests date recur filter plugin.
   */
  public function testDateRecurFilterAbsoluteMonth() {
    // Testing around September 2014.
    $entity1 = $this->createEntity();
    $entity1->{$this->fieldName} = [
      [
        // Before Sept 2014.
        'value' => '2014-08-12T23:00:00',
        'end_value' => '2014-08-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity1->save();
    $entity2 = $this->createEntity();
    $entity2->{$this->fieldName} = [
      [
        // Intersecting start of Sept 2014.
        'value' => '2014-08-29T23:00:00',
        'end_value' => '2014-09-02T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity2->save();
    $entity3 = $this->createEntity();
    $entity3->{$this->fieldName} = [
      [
        // Within Sept 2014.
        'value' => '2014-09-12T23:00:00',
        'end_value' => '2014-09-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity3->save();
    $entity4 = $this->createEntity();
    $entity4->{$this->fieldName} = [
      [
        // Intersecting end of Sept 2014.
        'value' => '2014-09-29T23:00:00',
        'end_value' => '2014-10-02T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity4->save();
    $entity5 = $this->createEntity();
    $entity5->{$this->fieldName} = [
      [
        // After Sept 2014.
        'value' => '2014-10-12T23:00:00',
        'end_value' => '2014-10-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity5->save();
    $entity6 = $this->createEntity();
    $entity6->{$this->fieldName} = [
      [
        // Covering entirety of Sept 2014.
        'value' => '2014-08-12T23:00:00',
        'end_value' => '2014-10-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity6->save();

    $exposedIdentifier = 'dr_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      'value_granularity' => 'month',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => 'dr_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    // Input values are in the users timezone.
    $this->assertFilter(
      [$exposedIdentifier => '2014-07'],
      [],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-08'],
      [
        ['id' => $entity1->id()],
        ['id' => $entity2->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09'],
      [
        ['id' => $entity2->id()],
        ['id' => $entity3->id()],
        ['id' => $entity4->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-10'],
      [
        ['id' => $entity4->id()],
        ['id' => $entity5->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-11'],
      [],
      $filterOptions
    );
  }

  /**
   * Tests date recur filter plugin.
   */
  public function testDateRecurFilterAbsoluteDay() {
    // Testing around 13 September 2014 in users local timezone.
    $entity1 = $this->createEntity();
    $entity1->{$this->fieldName} = [
      [
        // Before 13 September 2014.
        'value' => '2014-09-11T23:00:00',
        'end_value' => '2014-09-12T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity1->save();
    $entity2 = $this->createEntity();
    $entity2->{$this->fieldName} = [
      [
        // Intersecting start of 13 September 2014.
        // 11pm 12 September 2014.
        'value' => '2014-09-12T13:00:00',
        // 1am 13 September 2014.
        'end_value' => '2014-09-12T15:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity2->save();
    $entity3 = $this->createEntity();
    $entity3->{$this->fieldName} = [
      [
        // Within 13 September 2014.
        // 2am 13 September 2014.
        'value' => '2014-09-12T16:00:00',
        // 4am 13 September 2014.
        'end_value' => '2014-09-12T18:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity3->save();
    $entity4 = $this->createEntity();
    $entity4->{$this->fieldName} = [
      [
        // Intersecting end of 13 September 2014.
        // 10pm 13 September 2014.
        'value' => '2014-09-13T12:00:00',
        // 2am 14 September 2014.
        'end_value' => '2014-09-13T16:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity4->save();
    $entity5 = $this->createEntity();
    $entity5->{$this->fieldName} = [
      [
        // After 13 September 2014.
        // 2am 14 September 2014.
        'value' => '2014-09-13T16:00:00',
        // 4am 14 September 2014.
        'end_value' => '2014-09-13T18:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity5->save();
    $entity6 = $this->createEntity();
    $entity6->{$this->fieldName} = [
      [
        // Covering entirety of 13 September 2014.
        // 11pm 12 September 2014.
        'value' => '2014-09-12T13:00:00',
        // 4am 14 September 2014.
        'end_value' => '2014-09-13T18:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '0',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity6->save();

    $exposedIdentifier = 'dr_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      'value_granularity' => 'day',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => 'dr_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    // Input values are in the users timezone.
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-11'],
      [],
      $filterOptions,
      'day before'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-12'],
      [
        ['id' => $entity1->id()],
        ['id' => $entity2->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13'],
      [
        ['id' => $entity2->id()],
        ['id' => $entity3->id()],
        ['id' => $entity4->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-14'],
      [
        ['id' => $entity4->id()],
        ['id' => $entity5->id()],
        ['id' => $entity6->id()],
      ],
      $filterOptions
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-15'],
      [],
      $filterOptions,
      'day after'
    );
  }

  /**
   * Tests date recur filter plugin.
   */
  public function testDateRecurFilterAbsoluteSecond() {
    $entity = $this->createEntity();
    $entity->{$this->fieldName} = [
      [
        // 13 Sept 2014, 9-5am.
        'value' => '2014-09-12T23:00:00',
        'end_value' => '2014-09-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();

    // Decoy.
    $entity2 = $this->createEntity();
    $entity2->{$this->fieldName} = [
      [
        // 14 Sept 2014, 9-5am.
        'value' => '2014-09-13T23:00:00',
        'end_value' => '2014-09-14T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity2->save();

    $exposedIdentifier = 'dr_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      'value_granularity' => 'second',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => 'dr_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    $expectedRowWithEntity = [['id' => $entity->id()]];

    // Input values are in the users timezone.
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13T08:59:59'],
      [],
      $filterOptions,
      'before occurrence, no match'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13T09:00:00'],
      $expectedRowWithEntity,
      $filterOptions,
      'start of occurrence, match'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13T09:01:00'],
      $expectedRowWithEntity,
      $filterOptions,
      'within occurrence, match'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13T17:00:00'],
      $expectedRowWithEntity,
      $filterOptions,
      'end of occurrence, match'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13T17:00:01'],
      [],
      $filterOptions,
      'after occurrence, no match'
    );
  }

  /**
   * Tests timezone capability for non second granularity.
   *
   * There is different handling of timezones for seconds vs other
   * granularities.
   */
  public function testDateRecurFilterTimezoneNonSecond() {
    $entity = $this->createEntity();
    $entity->{$this->fieldName} = [
      [
        // 13 Sept 2014, 9-5am.
        'value' => '2014-09-12T23:00:00',
        'end_value' => '2014-09-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();

    $exposedIdentifier = 'dr_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      // Doesnt matter which granularity, so long as it is not seconds.
      'value_granularity' => 'day',
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => 'dr_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    $expectedRowWithEntity = [['id' => $entity->id()]];

    // Input values are in the users timezone.
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-12'],
      [],
      $filterOptions,
      'no match previous day'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-13'],
      $expectedRowWithEntity,
      $filterOptions,
      'match current day'
    );
    $this->assertFilter(
      [$exposedIdentifier => '2014-09-14'],
      [],
      $filterOptions,
      'no match folowing day'
    );
  }

  /**
   * Tests date recur filter plugin.
   *
   * If asserting successful validation, the raw input must be set up to return
   * one result matching the test entity.
   *
   * @param string $granularity
   *   Granularity.
   * @param string $rawInput
   *   User input.
   * @param bool $successfulValidate
   *   Whether the validation was successful.
   *
   * @dataProvider providerInvalidInput
   */
  public function testInvalidInput($granularity, $rawInput, $successfulValidate) {
    // Create a test entity.
    $entity = $this->createEntity();
    $entity->{$this->fieldName} = [
      [
        'value' => '2014-09-12T23:00:00',
        'end_value' => '2014-09-13T07:00:00',
        'rrule' => 'FREQ=DAILY;COUNT=1',
        'infinite' => '1',
        'timezone' => 'Australia/Sydney',
      ],
    ];
    $entity->save();

    $exposedIdentifier = 'dr_occurrences';
    $filterOptions = [
      'operator' => '=',
      'value' => '',
      'value_granularity' => $granularity,
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $exposedIdentifier,
        'operator' => 'dr_occurrences_op',
        'use_operator' => FALSE,
        'required' => FALSE,
      ],
    ];

    $input = [$exposedIdentifier => $rawInput];

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('dr_entity_test_list');
    $executable = $view->getExecutable();
    $executable->addHandler('default', 'filter', 'dr_entity_test', $this->fieldName . '_occurrences', $filterOptions);
    $executable->setExposedInput($input);

    $executable->execute();
    if ($successfulValidate) {
      $this->assertTrue(!isset($executable->build_info['abort']));

      $expectedRowWithEntity = [['id' => $entity->id()]];
      $this->assertFilter(
        $input,
        $expectedRowWithEntity,
        $filterOptions
      );
    }
    else {
      $this->assertTrue(isset($executable->build_info['abort']));
    }
  }

  /**
   * Data provider for testInvalidInput.
   *
   * @return array
   *   Data for testing.
   */
  public function providerInvalidInput() {
    $data = [];

    $data['year success 1'] = [
      'year',
      '2014',
      TRUE,
    ];
    $data['year failure 2'] = [
      'year',
      '205',
      FALSE,
    ];
    $data['year failure 3'] = [
      'year',
      '20145',
      FALSE,
    ];
    $data['month success 1'] = [
      'month',
      '2014-09',
      TRUE,
    ];
    $data['month failure 2'] = [
      'month',
      '2014-9',
      FALSE,
    ];
    $data['month failure 4'] = [
      'month',
      '2014-090',
      FALSE,
    ];
    $data['day success 1'] = [
      'day',
      '2014-09-13',
      TRUE,
    ];
    $data['day failure 2'] = [
      'day',
      '2014-09-3',
      FALSE,
    ];
    $data['day failure 3'] = [
      'day',
      '2014-09-113',
      FALSE,
    ];
    $data['second success 1'] = [
      'second',
      '2014-09-13T12:59:59',
      TRUE,
    ];
    $data['second failure 2'] = [
      'second',
      '2014-09-13T121:59:59',
      FALSE,
    ];
    $data['second failure 3'] = [
      'second',
      '2014-09-13T12:599:59',
      FALSE,
    ];
    $data['second failure 4'] = [
      'second',
      '2014-09-13T12:59:599',
      FALSE,
    ];

    return $data;
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

  /**
   * Asserts the filter plugin.
   *
   * @param array $input
   *   Input for exposed filters.
   * @param array $expectedResult
   *   The expected result.
   * @param array $filterOptions
   *   Options to set via exposed inputs.
   * @param string|null $message
   *   Message for phpunit.
   */
  protected function assertFilter(array $input, array $expectedResult, array $filterOptions, $message = NULL) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('dr_entity_test_list');
    $executable = $view->getExecutable();
    $executable->addHandler('default', 'filter', 'dr_entity_test', $this->fieldName . '_occurrences', $filterOptions);
    $executable->setExposedInput($input);
    $this->executeView($executable);

    $this->assertCount(count($expectedResult), $executable->result);
    $this->assertIdenticalResultset($executable, $expectedResult, $this->map, $message);

    // Must be destroyed after each run.
    $executable->destroy();
  }

}
