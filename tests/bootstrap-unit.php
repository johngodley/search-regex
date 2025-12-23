<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'PLUGIN_PATH', dirname( __DIR__ ) );
define( 'SEARCHREGEX_TESTS', true );

require PLUGIN_PATH . '/tests/unit-test.php';
