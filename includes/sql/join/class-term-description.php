<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Joins term_taxonomy table for description column
 */
class Term_Description extends Join {
	/**
	 * Constructor
	 *
	 * @param string $column Column name.
	 */
	public function __construct( $column ) {
		$this->column = $column;
	}

	public function get_select() {
		global $wpdb;

		return new Sql\Select\Select( Sql\Value::table( $wpdb->prefix . 'term_taxonomy' ), Sql\Value::column( 'description' ) );
	}

	public function get_from() {
		global $wpdb;

		return new Sql\From( Sql\Value::safe_raw( sprintf( 'INNER JOIN %sterm_taxonomy AS tt ON (%sterms.term_id = tt.term_id)', $wpdb->prefix, $wpdb->prefix ) ) );
	}

	public function get_join_column() {
		return 'tt.description';
	}

	public function get_join_value( $value ) {
		return "$value";
	}

	public function get_table() {
		return '';
	}
}
