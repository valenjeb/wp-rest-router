<?php

/**
 * PHPUnit bootstrap file.
 *
 * phpcs:disable Squiz.Functions.GlobalFunction.Found
 */

declare(strict_types=1);

$testsDir = getenv('WP_TESTS_DIR');

if (! $testsDir) {
    $testsDir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$phpunitPolyfillsPath = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if ($phpunitPolyfillsPath !== false) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $phpunitPolyfillsPath);
}

$functionsPath = $testsDir . '/includes/functions.php';

if (! file_exists($functionsPath)) {
    echo sprintf(
        'Could not find %s, have you run "bin/install-wp-tests.sh"?' . PHP_EOL,
        $functionsPath
    );
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $functionsPath;

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin(): void
{
    require dirname(dirname(__FILE__)) . '/wp-rest-router.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require $testsDir . '/includes/bootstrap.php';
