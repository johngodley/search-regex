<?php

namespace SearchRegex\Sql;

/**
 * AND a group of WHERE statements together
 */
class Sql_Where_And extends Sql_Where_Or {
	public function get_as_sql() {
		return $this->get_group( 'AND' );
	}
}
