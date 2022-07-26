<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

/**
 * WHERE for an integer
 */
class Where_Integer extends Where {
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

		parent::__construct( $column, $logic_sql, intval( $value, 10 ) );
	}

	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%d', $this->value );
	}
}
