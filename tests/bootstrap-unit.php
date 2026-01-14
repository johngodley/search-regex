<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'PLUGIN_PATH', dirname( __DIR__ ) );

// Only load Brain\Monkey TestCase if Brain\Monkey is available
if ( class_exists( 'Brain\Monkey\Container' ) ) {
	require PLUGIN_PATH . '/tests/unit-test.php';

	// Set up Brain\Monkey and define WordPress function stubs before tests run
	Brain\Monkey\setUp();

	Brain\Monkey\Functions\stubs( [
		'add_filter' => null,
		'add_action' => null,
		'is_admin' => false,
		'plugin_basename' => 'search-regex/search-regex.php',
	] );
}
