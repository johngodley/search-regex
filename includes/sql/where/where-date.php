<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

/**
 * WHERE for a date
 */
class Where_Date extends Where {
	/**
	 * Constructor
	 *
	 * @param Sql\Select\Select $column Column.
	 * @param string            $logic Logic.
	 * @param integer           $value Value.
	 */
	public function __construct( Sql\Select\Select $column, $logic, $value ) {
		$map = [
			'notequals' => '!=',
			'greater' => '>',
			'less' => '<',
		];

		$logic_sql = '=';
		if ( isset( $map[ $logic ] ) ) {
			$logic_sql = $map[ $logic ];
		}

		if ( in_array( $logic, [ '=', '>', '<', '!=', '<=', '>=' ], true ) ) {
			$logic_sql = $logic;
		}

		$value = date( 'Y-m-d H:i:s', intval( $value, 10 ) );

		parent::__construct( $column, $logic_sql, $value );
	}

	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%s', $this->value );
	}
}
