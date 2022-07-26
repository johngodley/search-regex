<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

/**
 * WHERE for a null value
 */
class Where_Null extends Where {
	/**
	 * Constructor
	 *
	 * @param Sql\Select\Select $column Column.
	 * @param string            $logic Logic.
	 */
	public function __construct( Sql\Select\Select $column, $logic ) {
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
