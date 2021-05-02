<?php

namespace SearchRegex\Sql;

class Sql_Where_In extends Sql_Where {
	public function __construct( Sql_Select $column, $logic, $value ) {
		$logic_sql = 'IN';

		if ( $logic === 'NOT IN' ) {
			$logic_sql = 'NOT IN';
		}

		parent::__construct( $column, $logic, $value );
	}

	public function get_value() {
		global $wpdb;

		$values = array_map( function( $item ) use ( $wpdb ) {
			if ( is_numeric( $item ) ) {
				return $wpdb->prepare( '%d', $item );
			}

			return $wpdb->prepare( '%s', $item );
		}, $this->value );

		return '(' . implode( ', ', $values ) . ')';
	}
}
