<?php
/**
 * PHPUnit bootstrap for wp-env / WordPress test suite.
 *
 * @package S2J_MLDC
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'TESTS_PLUGIN_FILE', TESTS_PLUGIN_DIR . '/s2j-media-library-date-corrector.php' );

$polyfills = TESTS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills ) ) {
	require_once $polyfills;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find ' . $_tests_dir . '/includes/functions.php. Run tests via wp-env (npm run test:unit:php).' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';
require_once __DIR__ . '/stubs/wordpress-test-functions.php';

/**
 * Load the plugin under test.
 *
 * @return void
 */
function _s2j_mldc_tests_load_plugin(): void {
	require TESTS_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_s2j_mldc_tests_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
