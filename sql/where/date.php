<?php

namespace SearchRegex\Sql;

/**
 * WHERE for a date
 */
class Sql_Where_Date extends Sql_Where {
	/**
	 * Constructor
	 *
	 * @param Sql_Select $column Column.
	 * @param string     $logic Logic.
	 * @param integer    $value Value.
	 */
	public function __construct( Sql_Select $column, $logic, $value ) {
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
