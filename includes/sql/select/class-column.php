<?php

namespace SearchRegex\Sql\Select;

use SearchRegex\Schema;
use SearchRegex\Sql;

/**
 * SQL SELECT for a Schema\Column
 */
class Select_Column extends Select {
	public function __construct( Schema\Column $column, Sql\Value $alias = null ) {
		parent::__construct( Sql\Value::table( $column->get_table() ), Sql\Value::column( $column->get_column() ), $alias );
	}
}
