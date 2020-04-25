<?php
/**
 * @file
 *   A bootstrap file for `phpunit` test runner.
 *
 * This file registers all Drupal modules and their test directories
 * for autoloading. The downside is that it is slower than bootstrap-fast.php.
 */


use DrupalFinder\DrupalFinder;

// Setup the global namespaces before including core's bootstrap so that the
// `ExistingSite` namespace is included.
// @see drupal_phpunit_populat_class_loader()
$GLOBALS['namespaces'] = dtt_phpunit_gather_namespaces();

// Now fallback to core's bootstrap.
require_once dtt_phpunit_find_root() . '/core/tests/bootstrap.php';

/**
 * Gather namespaces, merging core's and the `ExistingSite` namespace.
 *
 * @see \drupal_phpunit_get_extension_namespaces()
 *
 * @return array
 *   An associative array of extension directories, keyed by their namespace.
 */
function dtt_phpunit_gather_namespaces()
{
    $extension_roots = dtt_phpunit_test_extenstion_directory_roots();
    $extension_roots = array_filter($extension_roots, 'is_dir');
    $dirs = array_map('dtt_phpunit_find_extension_directories', $extension_roots);
    $dirs = array_reduce($dirs, 'array_merge', []);

    $suite_names = ['Unit', 'Kernel', 'Functional', 'FunctionalJavascript', 'ExistingSite', 'ExistingSiteJavascript'];
    $namespaces = [];
    foreach ($dirs as $extension => $dir) {
        if (is_dir($dir . '/src')) {
          // Register the PSR-4 directory for module-provided classes.
            $namespaces['Drupal\\' . $extension . '\\'][] = $dir . '/src';
        }
        $test_dir = $dir . '/tests/src';
        if (is_dir($test_dir)) {
            foreach ($suite_names as $suite_name) {
                $suite_dir = $test_dir . '/' . $suite_name;
                if (is_dir($suite_dir)) {
                  // Register the PSR-4 directory for PHPUnit-based suites.
                    $namespaces['Drupal\\Tests\\' . $extension . '\\' . $suite_name . '\\'][] = $suite_dir;
                }
            }
          // Extensions can have a \Drupal\extension\Traits namespace for
          // cross-suite trait code.
            $trait_dir = $test_dir . '/Traits';
            if (is_dir($trait_dir)) {
                $namespaces['Drupal\\Tests\\' . $extension . '\\Traits\\'][] = $trait_dir;
            }
        }
    }
    return $namespaces;
}

/**
 * Returns directories under which tests may exist.
 *
 * @see \drupal_phpunit_contrib_extension_directory_roots()
 *
 * @return array
 *   An array of directories under which tests may exist.
 */
function dtt_phpunit_test_extenstion_directory_roots()
{
    $root = dtt_phpunit_find_root();
    $paths = [
    $root . '/core/modules',
    $root . '/core/profiles',
    $root . '/modules',
    $root . '/profiles',
    $root . '/themes',
    ];
    $sites_path = $root . '/sites';
  // Note this also checks sites/../modules and sites/../profiles.
    foreach (scandir($sites_path) as $site) {
        if ($site[0] === '.' || $site === 'simpletest') {
            continue;
        }
        $path = "$sites_path/$site";
        $paths[] = is_dir("$path/modules") ? realpath("$path/modules") : null;
        $paths[] = is_dir("$path/profiles") ? realpath("$path/profiles") : null;
        $paths[] = is_dir("$path/themes") ? realpath("$path/themes") : null;
    }
    return array_filter($paths);
}

/**
 * Finds all valid extension directories recursively within a given directory.
 *
 * @param string $scan_directory
 *   The directory that should be recursively scanned.
 * @return array
 *   An associative array of extension directories found within the scanned
 *   directory, keyed by extension name.
 */
function dtt_phpunit_find_extension_directories($scan_directory)
{
    $extensions = [];
    $dirs = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            $scan_directory,
            \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
        )
    );
    foreach ($dirs as $dir) {
        if (strpos($dir->getPathname(), '.info.yml') !== false) {
          // Cut off ".info.yml" from the filename for use as the extension name. We
          // use getRealPath() so that we can scan extensions represented by
          // directory aliases.
            $extensions[substr($dir->getFilename(), 0, -9)] = $dir->getPathInfo()
            ->getRealPath();
        }
    }
    return $extensions;
}

/**
 * Finds Drupal root.
 *
 * Falls back to 'web' at the sibling directory to vendor.
 */
function dtt_phpunit_find_root()
{
    static $root;
    if ($root === null) {
        $finder = new DrupalFinder();
        $finder->locateRoot(getcwd());
        return $finder->getDrupalRoot();
    }
    return $root;
}
