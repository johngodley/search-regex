<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

/**
 * User meta data
 */
class Source_User_Meta extends Source_Meta {
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'usermeta';
	}

	public function get_table_id() {
		return 'umeta_id';
	}

	public function get_meta_object_id() {
		return 'user_id';
	}

	public function get_meta_table() {
		return 'user';
	}

	public function get_meta_name() {
		return __( 'User Meta', 'search-regex' );
	}

	/**
	 * Perform autocompletion on a column and a value
	 *
	 * @param array  $column Column.
	 * @param string $value  Value.
	 * @return array
	 */
	public function autocomplete( array $column, $value ) {
		if ( isset( $column['column'] ) && $column['column'] === $this->get_meta_object_id() ) {
			return Autocomplete::get_user( $value );
		}

		return parent::autocomplete( $column, $value );
	}
}
