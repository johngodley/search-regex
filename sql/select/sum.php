<?php

namespace SearchRegex\Sql;

class Sql_Select_Phrases extends Sql_Select_Column {
	private $values = [];

	public function __construct( \SearchRegex\Schema_Column $column, Sql_Value $alias = null ) {
		parent::__construct( $column, Sql_Value::raw( 'match_total' ) );

		// Not sanitized until later
		$this->values = [ [ 'column' => $column->get_column(), 'value' => $alias->get_value() ] ];
	}

	public function get_as_sql() {
		return '';
	}

	public function add_sum( Sql_Select_Phrases $phrase ) {
		$this->values = array_merge( $this->values, $phrase->values );
	}

	public function get_select_sql() {
		global $wpdb;

		$sum = [];

		foreach ( $this->values as $item ) {
			$column = $item['column'];
			$value = $item['value'];
			$cropped = mb_substr( $value, 0, mb_strlen( $value, 'UTF-8' ) - 1, 'UTF-8' );

			$sum[] = $wpdb->prepare( "SUM(CHAR_LENGTH($column) - CHAR_LENGTH(REPLACE(UPPER($column), UPPER(%s), UPPER(%s))))", $value, $cropped );
		}

		return implode( ' + ', $sum ) . ' as ' . $this->alias;
	}
}
