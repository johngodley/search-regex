<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

/**
 * WHERE for a IN
 */
class Where_In extends Where {
	/**
	 * Constructor
	 *
	 * @param Sql\Select\Select $column Column.
	 * @param string            $logic Logic.
	 * @param array             $value Value.
	 */
	public function __construct( Sql\Select\Select $column, $logic, $value ) {
		$logic_sql = 'IN';

		if ( $logic === 'NOT IN' ) {
			$logic_sql = 'NOT IN';
		}

		parent::__construct( $column, $logic_sql, $value );
	}

	public function get_value() {
		global $wpdb;

		if ( ! is_array( $this->value ) ) {
			return '';
		}

		$values = array_map( function( $item ) use ( $wpdb ) {
			if ( is_numeric( $item ) ) {
				return $wpdb->prepare( '%d', $item );
			}

			return $wpdb->prepare( '%s', $item );
		}, $this->value );

		return '(' . implode( ', ', $values ) . ')';
	}
}
