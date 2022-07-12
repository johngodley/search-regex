<?php
/**
 * @var String
 */
const ARRAY_A = '';

/**
 * @var String
 */
const SEARCHREGEX_FILE = '';

/**
 * @var String
 */
const SEARCHREGEX_MIN_WP = '';

/**
 * @var String
 */
const SEARCHREGEX_VERSION = '';

/**
 * @var String
 */
const SEARCHREGEX_BUILD = '';

class Red_Item {
	/**
	 * Get a redirect
	 *
	 * @param Int $id Item ID.
	 * @return Red_Item|\WP_Error Redirect
	 */
	public static function get_by_id( $id ) {
	}

	/**
	 * Update a redirect
	 *
	 * @param Array $json Item ID.
	 * @return \WP_Error|Bool
	 */
	public function update( array $json ) {
	}

	/**
	 * Convert to JSON
	 *
	 * @return Array
	 */
	public function to_json() {
	}
}
