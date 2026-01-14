<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'PLUGIN_PATH', dirname( __DIR__ ) );

// Only load Brain\Monkey TestCase if Brain\Monkey is available.
// Note: Brain\Monkey setUp/tearDown is handled per-test in the TestCase base class
// (see tests/unit-test.php) to ensure proper isolation between tests.
if ( class_exists( 'Brain\Monkey\Container' ) ) {
	require PLUGIN_PATH . '/tests/unit-test.php';

	// Define WordPress function stubs that are needed when loading plugin files.
	// These stubs allow the plugin's main file to be required without errors.
	// Individual tests can override these stubs in their setUp() methods.
	Brain\Monkey\Functions\stubs( [
		'add_filter' => null,
		'add_action' => null,
		'is_admin' => false,
		'plugin_basename' => 'search-regex/search-regex.php',
	] );
}
