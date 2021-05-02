<?php

namespace SearchRegex\Sql;

class Sql_From {
	private $table = null;
	private $alias = null;

	public function __construct( Sql_Value $table, Sql_Value $alias = null ) {
		$this->table = $table->get_value();
		$this->alias = $alias ? $alias->get_value() : null;
	}

	public function get_as_sql() {
		if ( $this->alias && $this->alias !== $this->table ) {
			return $this->table . ' AS ' . $this->alias;
		}

		return $this->table;
	}
}
