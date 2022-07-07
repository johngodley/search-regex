<?php

namespace SearchRegex\Sql;

/**
 * WHERE for a null value
 */
class Sql_Where_Null extends Sql_Where {
	/**
	 * Constructor
	 *
	 * @param Sql_Select $column Column.
	 * @param string     $logic Logic.
	 */
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
