<?php

namespace SearchRegex\Sql;

class Sql_Where_Null extends Sql_Where {
	public function __construct( Sql_Select $column, $logic ) {
		$logic_sql = 'IS';
		if ( $logic === 'has' ) {
			$logic_sql = 'IS NOT';
		}

		parent::__construct( $column, $logic_sql, 'NULL' );
	}

	public function get_value() {
		return 'NULL';
	}
}
