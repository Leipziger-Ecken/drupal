<?php

namespace Drupal\Tests\typed_data\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Tests using typed data filters.
 *
 * @group typed_data
 *
 * @coversDefaultClass \Drupal\typed_data\DataFilterManager
 */
class DataFilterTest extends EntityKernelTestBase {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The data filter manager.
   *
   * @var \Drupal\typed_data\DataFilterManagerInterface
   */
  protected $dataFilterManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['typed_data', 'node', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->typedDataManager = $this->container->get('typed_data_manager');
    $this->dataFilterManager = $this->container->get('plugin.manager.typed_data_filter');

    // Make sure default date formats are available
    // for testing the format_date filter.
    $this->installConfig(['system']);

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\LowerFilter
   */
  public function testLowerFilter() {
    $filter = $this->dataFilterManager->createInstance('lower');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), 'tEsT');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals('test', $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\DefaultFilter
   */
  public function testDefaultFilter() {
    $filter = $this->dataFilterManager->createInstance('default');
    $data = $this->typedDataManager->create(DataDefinition::create('string'));

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertSame($data->getDataDefinition(), $filter->filtersTo($data->getDataDefinition(), ['default']));

    $fails = $filter->validateArguments($data->getDataDefinition(), []);
    $this->assertEquals(1, count($fails));
    // @todo In Drupal 8.8.x PHPUnit 9 functions need to be used otherwise
    // the tests will fail with a deprecation error.
    // Remove this once 8.7.x is unsupported.
    // @see https://www.drupal.org/project/typed_data/issues/3138469
    if (version_compare(substr(\Drupal::VERSION, 0, 3), '8.8', '>=')) {
      $this->assertStringContainsString('Missing arguments', (string) $fails[0]);
    }
    else {
      $this->assertContains('Missing arguments', (string) $fails[0]);
    }
    $fails = $filter->validateArguments($data->getDataDefinition(), [new \stdClass()]);
    $this->assertEquals(1, count($fails));
    $this->assertEquals('This value should be of the correct primitive type.', $fails[0]);

    $this->assertEquals('default', $filter->filter($data->getDataDefinition(), $data->getValue(), ['default']));
    $data->setValue('non-default');
    $this->assertEquals('non-default', $filter->filter($data->getDataDefinition(), $data->getValue(), ['default']));
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\FormatDateFilter
   */
  public function testFormatDateFilter() {
    $filter = $this->dataFilterManager->createInstance('format_date');
    $data = $this->typedDataManager->create(DataDefinition::create('timestamp'), 3700);

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $fails = $filter->validateArguments($data->getDataDefinition(), []);
    $this->assertEquals(0, count($fails));
    $fails = $filter->validateArguments($data->getDataDefinition(), ['medium']);
    $this->assertEquals(0, count($fails));
    $fails = $filter->validateArguments($data->getDataDefinition(), ['invalid-format']);
    $this->assertEquals(1, count($fails));
    $fails = $filter->validateArguments($data->getDataDefinition(), ['custom']);
    $this->assertEquals(1, count($fails));
    $fails = $filter->validateArguments($data->getDataDefinition(), ['custom', 'Y']);
    $this->assertEquals(0, count($fails));

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $this->assertEquals($date_formatter->format(3700), $filter->filter($data->getDataDefinition(), $data->getValue(), []));
    $this->assertEquals($date_formatter->format(3700, 'short'), $filter->filter($data->getDataDefinition(), $data->getValue(), ['short']));
    $this->assertEquals('1970', $filter->filter($data->getDataDefinition(), $data->getValue(), ['custom', 'Y']));

    // Verify the filter works with non-timestamp data as well.
    $data = $this->typedDataManager->create(DataDefinition::create('datetime_iso8601'), "1970-01-01T10:10:10+00:00");
    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());
    $this->assertEquals('1970', $filter->filter($data->getDataDefinition(), $data->getValue(), ['custom', 'Y']));

    // Test cache dependencies of date format config entities are added in.
    $metadata = new BubbleableMetadata();
    $filter->filter($data->getDataDefinition(), $data->getValue(), ['short'], $metadata);
    $this->assertEquals(DateFormat::load('short')->getCacheTags(), $metadata->getCacheTags());
    $metadata = new BubbleableMetadata();
    $filter->filter($data->getDataDefinition(), $data->getValue(), ['custom', 'Y'], $metadata);
    $this->assertEquals([], $metadata->getCacheTags());
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\StripTagsFilter
   */
  public function testStripTagsFilter() {
    $filter = $this->dataFilterManager->createInstance('striptags');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), '<b>Test <em>striptags</em> filter</b>');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals('Test striptags filter', $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\EntityUrlFilter
   */
  public function testEntityUrlFilter() {
    /* @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'title' => 'Test node',
      'type' => 'page',
    ]);
    $node->save();

    $filter = $this->dataFilterManager->createInstance('entity_url');
    $data = $this->typedDataManager->create(EntityDataDefinition::create('node'));
    $data->setValue($node);

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('uri', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    // Test the output of the filter.
    $output = $filter->filter($data->getDataDefinition(), $data->getValue(), []);
    $this->assertEquals($node->toUrl('canonical', ['absolute' => TRUE])->toString(), $output);
  }

  /**
   * @covers \Drupal\typed_data\Plugin\TypedDataFilter\EntityUrlFilter
   */
  public function testFileEntityUrlFilter() {
    file_put_contents('public://example.txt', $this->randomMachineName());
    /* @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $file->save();

    $filter = $this->dataFilterManager->createInstance('entity_url');
    $data = $this->typedDataManager->create(EntityDataDefinition::create('file'));
    $data->setValue($file);

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('uri', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    // Test the output of the filter.
    $output = $filter->filter($data->getDataDefinition(), $data->getValue(), []);
    $this->assertEquals($file->createFileUrl(FALSE), $output);
  }

}
