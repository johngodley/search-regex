<?php
/**
 * Legacy autoloader - retained for backward compatibility.
 *
 * This file was used in version 3.3.0 to load the plugin, but in retrospect it is better
 * to have it in the main plugin file.
 *
 * This file can safely be removed in a future major version (3.5+) after sufficient time
 * has passed for caches to be cleared.
 *
 * @deprecated 3.5.0 Use the autoloader in search-regex.php instead.
 * @package Search_Regex
 */

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

require_once __DIR__ . '/build/search-regex-version.php';
