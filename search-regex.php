<?php
/*
Plugin Name: Search Regex
Plugin URI: https://searchregex.com/
Description: Adds search and replace functionality across posts, pages, comments, and meta-data, with full regular expression support
Version: 3.3.0
Author: John Godley
Requires PHP: 7.4
Requires at least: 6.5
Text Domain: search-regex
============================================================================================================
For full license details see license.txt
============================================================================================================
*/

define( 'SEARCHREGEX_FILE', __FILE__ );

// This file must support PHP < 7.2 so as not to crash
if ( version_compare( phpversion(), '7.2' ) < 0 ) {
	// @phpstan-ignore-next-line
	add_filter( 'plugin_action_links_' . basename( dirname( SEARCHREGEX_FILE ) ) . '/' . basename( SEARCHREGEX_FILE ), 'searchregex_deprecated_php', 10, 4 );

	/**
	 * Show a deprecated PHP warning in the plugin page
	 *
	 * @param string[] $links Plugin links.
	 * @return string[]
	 */
	function searchregex_deprecated_php( $links ) {
		/* translators: 1: server PHP version. 2: required PHP version. */
		array_unshift( $links, '<a href="https://searchregex.com/support/problems/php-version/" style="color: red; text-decoration: underline">' . sprintf( __( 'Disabled! Detected PHP %1$s, need PHP %2$s+', 'search-regex' ), phpversion(), '5.6' ) . '</a>' );
		return $links;
	}

	return;
}

require_once __DIR__ . '/search-regex-loader.php';

if ( is_admin() ) {
	SearchRegex\Admin\Admin::init();
} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Trigger autoloader
	class_exists( SearchRegex\Cli\Search_Regex_CLI::class );
}

add_action( 'rest_api_init', function () {
	SearchRegex\Api\Api::init();
} );
