<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'PLUGIN_PATH', dirname( __DIR__ ) );
define( 'SEARCHREGEX_TESTS', true );

// Only load Brain\Monkey TestCase if Brain\Monkey is available
if ( class_exists( 'Brain\Monkey' ) ) {
	require PLUGIN_PATH . '/tests/unit-test.php';
}
