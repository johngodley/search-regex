<?php

namespace SearchRegex\Sql;

class Sql_Where_Date extends Sql_Where {
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

		$value = date( 'Y-m-d H:i:s', $value );

		parent::__construct( $column, $logic_sql, $value );
	}

	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%s', $this->value );
	}
}
