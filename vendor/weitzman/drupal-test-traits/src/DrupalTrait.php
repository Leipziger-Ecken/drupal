<?php

namespace weitzman\DrupalTestTraits;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\EntityInterface;
use DrupalFinder\DrupalFinder;
use Symfony\Component\HttpFoundation\Request;

trait DrupalTrait
{

    /**
     * A flag to track when we've restored the error handler.
     *
     * @var bool
     */
    protected static $restoredErrorHandler = false;

    /**
     * Entities to clean up.
     *
     * @var \Drupal\Core\Entity\EntityInterface[]
     */
    protected $cleanupEntities = [];

    /**
     * The container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * The Drupal kernel.
     *
     * @var \Drupal\Core\DrupalKernel
     */
    protected $kernel;

    /**
     * Bootstrap Drupal. Call this from your setUp() method.
     */
    public function setupDrupal()
    {
        // Bootstrap Drupal so we can use Drupal's built in functions.
        $finder = new DrupalFinder();
        $finder->locateRoot(getcwd());
        $classLoader = include $finder->getVendorDir() . '/autoload.php';
        $parsed_url = parse_url($this->baseUrl);
        $server = [
            'SCRIPT_FILENAME' => getcwd() . '/index.php',
            'SCRIPT_NAME' => isset($parsed_url['path']) ? $parsed_url['path'] . '/index.php' : '/index.php',
        ];
        $request = Request::create($this->baseUrl, 'GET', [], [], [], $server);
        $this->kernel = DrupalKernel::createFromRequest(
            $request,
            $classLoader,
            'existing-site-testcase',
            false,
            $finder->getDrupalRoot()
        );

        // The DrupalKernel only initializes the environment once which is where
        // it sets the Drupal error handler. We can therefore only restore it
        // once.
        if (!static::$restoredErrorHandler) {
            restore_error_handler();
            restore_exception_handler();
            static::$restoredErrorHandler = true;
        }

        chdir(DRUPAL_ROOT);
        $this->kernel->boot();
        $this->kernel->preHandle($request);
        $this->container = $this->kernel->getContainer();

        // Set up the browser test output file.
        $this->siteDirectory = 'dtt';
        $this->initBrowserOutputFile();
    }

    /**
     * Delete marked entities. Call this from your case's tearDown() method.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function tearDownDrupal()
    {
        foreach ($this->cleanupEntities as $entity) {
            $entity->delete();
        }
      // Avoid leaking memory in test cases (which are retained for a long time)
      // by removing references to all the things.
        $this->cleanupEntities = [];
        $this->kernel->shutdown();
        $this->kernel = null;
        $this->container = null;
    }

    /**
     * Mark an entity for deletion.
     *
     * Any entities you create when running against an installed site should be
     * flagged for deletion to ensure isolation between tests.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   Entity to delete.
     */
    protected function markEntityForCleanup(EntityInterface $entity)
    {
        $this->cleanupEntities[] = $entity;
    }
}
