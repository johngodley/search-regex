<?php

namespace {
	/**
	 * @var string
	 */
	const ARRAY_A = '';

	/**
	 * @var string
	 */
	const SEARCHREGEX_FILE = '';

	/**
	 * @var string
	 */
	const SEARCHREGEX_MIN_WP = '';

	/**
	 * @var string
	 */
	const SEARCHREGEX_VERSION = '';

	/**
	 * @var string
	 */
	const SEARCHREGEX_BUILD = '';

	const SEARCHREGEX_MIN_WP = '1';
	const SEARCHREGEX_BUILD = '';

	class Red_Item {
		/**
		 * Get a redirect
		 *
		 * @param int $id Item ID.
		 * @return Red_Item|\WP_Error Redirect
		 */
		public static function get_by_id( $id ) {
		}

		/**
		 * Update a redirect
		 *
		 * @param array $json Item ID.
		 * @return \WP_Error|bool
		 */
		public function update( array $json ) {
		}

		/**
		 * Convert to JSON
		 *
		 * @return array
		 */
		public function to_json() {
		}
	}
}

namespace WP_CLI\Utils {
	/**
	 * Format items for display
	 *
	 * @param string $format Output format.
	 * @param array<array<string, mixed>> $items Items to format.
	 * @param array<string> $fields Fields to display.
	 * @return void
	 */
	function format_items( $format, $items, $fields ) {
		// Stub function for PHPStan
	}
}
