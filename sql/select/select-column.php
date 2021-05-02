<?php

namespace SearchRegex\Sql;

class Sql_Select_Column extends Sql_Select {
	public function __construct( \SearchRegex\Schema_Column $column, Sql_Value $alias = null ) {
		parent::__construct( Sql_Value::table( $column->get_table() ), Sql_Value::column( $column->get_column() ), $alias );
	}
}
