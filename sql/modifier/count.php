<?php

namespace SearchRegex\Sql;

/**
 * Perform a COUNT of a query
 */
class Sql_Select_Count_Id extends Sql_Modifier {
	/**
	 * Column to modify
	 *
	 * @readonly
	 * @var string
	 */
	private $column;

	/**
	 * Constructor
	 *
	 * @param Sql_Value $table Table name.
	 * @param Sql_Value $table_id Table ID.
	 * @param string    $alias Count alias.
	 */
	public function __construct( Sql_Value $table, Sql_Value $table_id, $alias = 'match_rows' ) {
		$this->column = 'COUNT(' . $table->get_value() . '.' . $table_id->get_value() . ") AS $alias";
	}

	public function get_select( array $select, array $joins ) {
		return [ $this->column ];
	}

	public function get_group( array $group, array $joins ) {
		return [];
	}
}
