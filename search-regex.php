<?php
/*
Plugin Name: Search Regex
Plugin URI: https://searchregex.com/
Description: Adds search and replace functionality across posts, pages, comments, and meta-data, with full regular expression support
Version: 3.4
Author: John Godley
Requires PHP: 7.4
Requires at least: 6.5
Text Domain: search-regex
============================================================================================================
For full license details see license.txt
============================================================================================================
*/

define( 'SEARCHREGEX_FILE', __FILE__ );

// This file must support PHP < 7.4 so as not to crash
if ( version_compare( phpversion(), '7.4' ) < 0 ) {
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
		array_unshift( $links, '<a href="https://searchregex.com/support/problems/php-version/" style="color: red; text-decoration: underline">' . sprintf( __( 'Disabled! Detected PHP %1$s, need PHP %2$s+', 'search-regex' ), phpversion(), '7.4' ) . '</a>' );
		return $links;
	}

	return;
}

spl_autoload_register(
	function ( $requested_class ) {
		$prefix = 'SearchRegex\\';

		if ( strncmp( $prefix, $requested_class, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative_class = substr( $requested_class, strlen( $prefix ) );
		if ( $relative_class === '' ) {
			return;
		}

		$normalize = static function ( string $value ): string {
			return str_replace( '_', '-', strtolower( $value ) );
		};

		$segments = explode( '\\', $relative_class );
		$class_name = array_pop( $segments );
		$normalized_segments = array_filter(
			array_map( $normalize, $segments ),
			static fn ( $value ) => $value !== ''
		);

		$base_dir = __DIR__ . '/includes/';
		if ( count( $normalized_segments ) > 0 ) {
			$base_dir .= implode( '/', $normalized_segments ) . '/';
		}

		$path = $base_dir . 'class-' . $normalize( $class_name ) . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

// Check if the version is defined. This is to help with mid-update errors.
if ( file_exists( __DIR__ . '/build/search-regex-version.php' ) ) {
	require_once __DIR__ . '/build/search-regex-version.php';
}

/**
 * Clear PHP opcache when plugin is updated. This is to help with mid-update errors.
 *
 * @param object $upgrader The upgrader object.
 * @param array{action: string, type: string, plugins?: string[]} $options The upgrade options.
 * @return void
 */
function searchregex_clear_opcache_on_upgrade( $upgrader, $options ) {
	if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
		return;
	}

	$plugin_basename = plugin_basename( SEARCHREGEX_FILE );
	$plugins = $options['plugins'] ?? [];

	if ( ! in_array( $plugin_basename, $plugins, true ) ) {
		return;
	}

	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
}

add_action( 'upgrader_process_complete', 'searchregex_clear_opcache_on_upgrade', 10, 2 );

if ( is_admin() ) {
	// Check if it exists. Version 3.3 updated a lot of files and this might stop some mid-update errors.
	if ( ! class_exists( SearchRegex\Admin\Admin::class ) ) {
		return;
	}

	SearchRegex\Admin\Admin::init();
} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Trigger autoloader
	class_exists( SearchRegex\Cli\Search_Regex_CLI::class );
}

add_action( 'rest_api_init', function () {
	SearchRegex\Api\Api::init();
} );
