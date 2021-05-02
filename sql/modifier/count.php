<?php

namespace SearchRegex\Sql;

class Sql_Select_Count_Id extends Sql_Modifier {
	private $column;

	public function __construct( $table, $table_id, $alias = 'match_rows' ) {
		$table_id = preg_replace( '/\w*\./', '', $table_id );
		$this->column = "COUNT(DISTINCT ${table}.${table_id}) AS $alias";
	}

	public function get_select( array $select, array $joins ) {
		$sums = null;

		foreach ( $select as $item ) {
			if ( $item instanceof Sql_Select_Phrases ) {
				if ( $sums === null ) {
					$sums = $item;
				} else {
					$sums->add_sum( $item );
				}
			}
		}

		if ( $sums ) {
			return [ $this->column, $sums->get_select_sql() ];
		}

		return [ $this->column ];
	}

	public function get_group( array $group, array $joins ) {
		return [];
	}
}
