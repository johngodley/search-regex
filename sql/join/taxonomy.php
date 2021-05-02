<?php

namespace SearchRegex\Sql;

class Sql_Join_Taxonomy extends Sql_Join {
	public function __construct( $term_type ) {
		$this->column = $term_type;
	}

	public function get_select() {
		global $wpdb;

		return new Sql_Select( Sql_Value::table( $wpdb->prefix . 'term_taxonomy' ), Sql_Value::raw( "taxonomy" ) );
	}

	public function get_from() {
		global $wpdb;

		return new Sql_From( Sql_Value::raw( sprintf( 'INNER JOIN %sterm_taxonomy AS tt ON (%sterms.term_id = tt.term_id)', $wpdb->prefix, $wpdb->prefix ) ) );
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
