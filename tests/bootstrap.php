<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Cheetaho_Wp_Image_Optimizer
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
putenv( 'CHEETAHO_TEST_MODE=true' );
putenv( 'TEST_JPG_IMAGE_REMOTE_PATH=https://app.cheetaho.com/storage/demo/underC.jpg' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/cheetaho.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
