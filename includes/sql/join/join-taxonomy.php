<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Joins taxonomy table
 */
class Taxonomy extends Join {
	/**
	 * Constructor
	 *
	 * @param string $term_type Type of taxonomy.
	 */
	public function __construct( $term_type ) {
		$this->column = $term_type;
	}

	public function get_select() {
		global $wpdb;

		return new Sql\Select\Select( Sql\Value::table( $wpdb->prefix . 'term_taxonomy' ), Sql\Value::column( 'taxonomy' ) );
	}

	public function get_from() {
		global $wpdb;

		return new Sql\From( Sql\Value::safe_raw( sprintf( 'INNER JOIN %sterm_taxonomy AS tt ON (%sterms.term_id = tt.term_id)', $wpdb->prefix, $wpdb->prefix ) ) );
	}

	public function get_join_column() {
		return 'tt.taxonomy';
	}

	public function get_join_value( $value ) {
		$tax = get_taxonomy( $value );

		if ( $tax && ! is_wp_error( $tax ) ) {
			return $tax->label;
		}

		return "$value";
	}

	public function get_table() {
		return '';
	}
}
