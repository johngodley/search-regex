<?php

namespace SearchRegex\Sql;

class Sql_Group {
	private $group = null;

	public function __construct( Sql_Value $group ) {
		$this->group = $group->get_value();
	}

	public function get_as_sql() {
		return $this->group;
	}
}
