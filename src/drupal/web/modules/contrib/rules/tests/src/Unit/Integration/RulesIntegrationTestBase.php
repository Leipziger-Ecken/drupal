<?php

namespace Drupal\Tests\rules\Unit\Integration;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\rules\Core\ConditionManager;
use Drupal\rules\Context\DataProcessorManager;
use Drupal\rules\Core\RulesActionManager;
use Drupal\rules\Engine\ExpressionManager;
use Drupal\typed_data\DataFetcher;
use Drupal\typed_data\DataFilterManager;
use Drupal\typed_data\PlaceholderResolver;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\rules\Unit\TestMessenger;
use Prophecy\Argument;

/**
 * Base class for Rules integration tests.
 *
 * Rules integration tests leverage the services (plugin managers) of the Rules
 * module to test the integration of an action or condition. Dependencies on
 * other 3rd party modules or APIs can and should be mocked; e.g. the action
 * to delete an entity would mock the call to the entity API.
 */
abstract class RulesIntegrationTestBase extends UnitTestCase {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * @var \Drupal\rules\Core\RulesActionManagerInterface
   */
  protected $actionManager;

  /**
   * @var \Drupal\Core\Path\AliasManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\rules\Core\ConditionManager
   */
  protected $conditionManager;

  /**
   * @var \Drupal\rules\Engine\ExpressionManager
   */
  protected $rulesExpressionManager;

  /**
   * @var \Drupal\rules\Context\DataProcessorManager
   */
  protected $rulesDataProcessorManager;

  /**
   * A mocked Rules logger.channel.rules_debug service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $logger;

  /**
   * All setup'ed namespaces.
   *
   * @var \ArrayObject
   */
  protected $namespaces;

  /**
   * @var \Drupal\Core\Cache\NullBackend
   */
  protected $cacheBackend;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface||\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * Array object keyed with module names and TRUE as value.
   *
   * @var \ArrayObject
   */
  protected $enabledModules;

  /**
   * The Drupal service container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * The class resolver mock for the typed data manager.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $classResolver;

  /**
   * The data fetcher service.
   *
   * @var \Drupal\typed_data\DataFetcher
   */
  protected $dataFetcher;

  /**
   * The placeholder resolver service.
   *
   * @var \Drupal\typed_data\PlaceholderResolver
   */
  protected $placeholderResolver;

  /**
   * The data filter manager.
   *
   * @var \Drupal\typed_data\DataFilterManager
   */
  protected $dataFilterManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    // Register plugin managers used by Rules, but mock some unwanted
    // dependencies requiring more stuff to loaded.
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    // Set all the modules as being existent.
    $this->enabledModules = new \ArrayObject();
    $this->enabledModules['rules'] = TRUE;
    $this->enabledModules['rules_test'] = TRUE;
    $enabled_modules = $this->enabledModules;
    $this->moduleHandler->moduleExists(Argument::type('string'))
      ->will(function ($arguments) use ($enabled_modules) {
        if (isset($enabled_modules[$arguments[0]])) {
          return [$arguments[0], $enabled_modules[$arguments[0]]];
        }
        // Handle case where a plugin provider module is not enabled.
        return [$arguments[0], FALSE];
      });

    // We don't care about alter() calls on the module handler.
    $this->moduleHandler->alter(Argument::any(), Argument::any(), Argument::any(), Argument::any())
      ->willReturn(NULL);

    $this->cacheBackend = new NullBackend('rules');
    $rules_directory = __DIR__ . '/../../../..';
    $this->namespaces = new \ArrayObject([
      'Drupal\\rules' => $rules_directory . '/src',
      'Drupal\\rules_test' => $rules_directory . '/tests/modules/rules_test/src',
      'Drupal\\Core\\TypedData' => $this->root . '/core/lib/Drupal/Core/TypedData',
      'Drupal\\Core\\Validation' => $this->root . '/core/lib/Drupal/Core/Validation',
    ]);

    $this->actionManager = new RulesActionManager($this->namespaces, $this->cacheBackend, $this->moduleHandler->reveal());
    $this->conditionManager = new ConditionManager($this->namespaces, $this->cacheBackend, $this->moduleHandler->reveal());

    $uuid_service = new Php();
    $this->rulesExpressionManager = new ExpressionManager($this->namespaces, $this->moduleHandler->reveal(), $uuid_service);

    $this->classResolver = $this->prophesize(ClassResolverInterface::class);

    $this->typedDataManager = new TypedDataManager(
      $this->namespaces,
      $this->cacheBackend,
      $this->moduleHandler->reveal(),
      $this->classResolver->reveal()
    );
    $this->rulesDataProcessorManager = new DataProcessorManager($this->namespaces, $this->moduleHandler->reveal());

    $this->aliasManager = $this->prophesize(AliasManagerInterface::class);

    // Keep the deprecated entity manager around because it is still used in a
    // few places.
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getDefinitions()->willReturn([]);

    // Setup a rules_component storage mock which returns nothing by default.
    $storage = $this->prophesize(ConfigEntityStorageInterface::class);
    $storage->loadMultiple(NULL)->willReturn([]);
    $this->entityTypeManager->getStorage('rules_component')->willReturn($storage->reveal());

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityFieldManager->getBaseFieldDefinitions()->willReturn([]);

    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityTypeBundleInfo->getBundleInfo()->willReturn([]);

    $this->dataFetcher = new DataFetcher();
    $this->messenger = new TestMessenger();

    $this->dataFilterManager = new DataFilterManager($this->namespaces, $this->cacheBackend, $this->moduleHandler->reveal());
    $this->placeholderResolver = new PlaceholderResolver($this->dataFetcher, $this->dataFilterManager);

    // Mock the Rules debug logger service and make it return our mocked logger.
    $this->logger = $this->prophesize(LoggerChannelInterface::class);

    $container->set('entity.manager', $this->entityManager->reveal());
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());
    $container->set('entity_type.bundle.info', $this->entityTypeBundleInfo->reveal());
    $container->set('context.repository', new LazyContextRepository($container, []));
    $container->set('logger.channel.rules_debug', $this->logger->reveal());
    $container->set('path.alias_manager', $this->aliasManager->reveal());
    $container->set('plugin.manager.rules_action', $this->actionManager);
    $container->set('plugin.manager.condition', $this->conditionManager);
    $container->set('plugin.manager.rules_expression', $this->rulesExpressionManager);
    $container->set('plugin.manager.rules_data_processor', $this->rulesDataProcessorManager);
    $container->set('messenger', $this->messenger);
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('uuid', $uuid_service);
    $container->set('typed_data.data_fetcher', $this->dataFetcher);
    $container->set('typed_data.placeholder_resolver', $this->placeholderResolver);

    \Drupal::setContainer($container);
    $this->container = $container;
  }

  /**
   * Fakes the enabling of a module and adds its namespace for plugin loading.
   *
   * This method allows plugins provided by a module to be discoverable.
   *
   * @param string $name
   *   The name of the module that's going to be enabled.
   * @param array $namespaces
   *   Map of the association between module's namespaces and filesystem paths.
   */
  protected function enableModule($name, array $namespaces = []) {
    $this->enabledModules[$name] = TRUE;

    if (empty($namespaces)) {
      $namespaces = ['Drupal\\' . $name => $this->root . '/' . $this->constructModulePath($name) . '/src'];
    }
    foreach ($namespaces as $namespace => $path) {
      $this->namespaces[$namespace] = $path;
    }
  }

  /**
   * Determines the path to a module's class files.
   *
   * Core modules and contributed modules are located in different places, and
   * the testbot (DrupalCI) does not use same directory structure as most live
   * Drupal sites, so we must discover the path instead of hardwiring it.
   *
   * This method discovers modules the same way as Drupal core, so it should
   * work for core and contributed modules in all environments.
   *
   * @see \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected function constructModulePath($module) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($this->root, $flags);

    // Filter the recursive scan to discover extensions only.
    // Important: Without a RecursiveFilterIterator, RecursiveDirectoryIterator
    // would recurse into the entire filesystem directory tree without any kind
    // of limitations.
    $filter = new RecursiveExtensionFilterIterator($directory_iterator);
    // Ensure we find testing modules too!
    $filter->acceptTests(TRUE);

    // The actual recursive filesystem scan is only invoked by instantiating the
    // RecursiveIteratorIterator.
    $iterator = new \RecursiveIteratorIterator($filter,
      \RecursiveIteratorIterator::LEAVES_ONLY,
      // Suppress filesystem errors in case a directory cannot be accessed.
      \RecursiveIteratorIterator::CATCH_GET_CHILD

    );

    $info_files = new \RegexIterator($iterator, "/^$module.info.yml$/");
    foreach ($info_files as $file) {
      // There should only be one match.
      return $file->getSubPath();
    }
  }

  /**
   * Returns a typed data object.
   *
   * This helper for quick creation of typed data objects.
   *
   * @param string $data_type
   *   The data type to create an object for.
   * @param mixed $value
   *   The value to set.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The created object.
   */
  protected function getTypedData($data_type, $value) {
    $definition = $this->typedDataManager->createDataDefinition($data_type);
    $data = $this->typedDataManager->create($definition);
    $data->setValue($value);
    return $data;
  }

  /**
   * Helper method to mock irrelevant cache methods on entities.
   *
   * @param string $interface
   *   The interface that should be mocked, example: EntityInterface::class.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ProphecyInterface
   *   The mocked entity.
   */
  protected function prophesizeEntity($interface) {
    $entity = $this->prophesize($interface);
    // Cache methods are irrelevant for the tests but might be called.
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCacheTags()->willReturn([]);
    $entity->getCacheMaxAge()->willReturn(0);
    return $entity;
  }

}
