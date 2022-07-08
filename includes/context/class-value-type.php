<?php

namespace SearchRegex\Context;

/**
 * Represents the type of a particular value
 */
class Value_Type {
	const VALUE_TEXT = 'text';
	const VALUE_PHP = 'php';
	const VALUE_JSON = 'json';
	const VALUE_BLOCKS = 'blocks';
	const VALUE_HTML = 'html';

	/**
	 * Get the type for the value
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function get( $value ) {
		// Detect type
		if ( is_serialized( $value ) ) {
			return self::VALUE_PHP;
		} elseif ( preg_match( '/^[\[\|\{]/', $value ) ) {
			return self::VALUE_JSON;
		} elseif ( strpos( $value, '<!-- wp:' ) !== false ) {
			return self::VALUE_BLOCKS;
		} elseif ( preg_match( '@</.*?>@', $value ) ) {
			return self::VALUE_HTML;
		}

		return self::VALUE_TEXT;
	}
}
