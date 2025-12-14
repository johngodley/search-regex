<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'PLUGIN_PATH', dirname( __DIR__ ) );
define( 'SEARCHREGEX_TESTS', true );

require PLUGIN_PATH . '/tests/unit-test.php';
require_once PLUGIN_PATH . '/includes/search/class-search.php';
require_once PLUGIN_PATH . '/includes/filter/class-search-filter.php';
require_once PLUGIN_PATH . '/includes/modifier/class-modifier.php';
require_once PLUGIN_PATH . '/includes/source/class-source.php';
require_once PLUGIN_PATH . '/includes/schema/class-schema.php';
require_once PLUGIN_PATH . '/includes/context/class-context.php';
require_once PLUGIN_PATH . '/includes/action/class-action.php';
require_once PLUGIN_PATH . '/includes/sql/class-sql.php';