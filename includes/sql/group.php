<?php

namespace SearchRegex\Sql;

/**
 * Sql grouping
 */
class Sql_Group {
	/**
	 * Group name
	 *
	 * @readonly
	 * @var string
	 */
	private $group;

	public function __construct( Sql_Value $group ) {
		$this->group = $group->get_value();
	}

	/**
	 * Get the group name
	 *
	 * @return string
	 */
	public function get_as_sql() {
		return $this->group;
	}
}
