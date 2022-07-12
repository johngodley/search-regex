<?php

namespace SearchRegex\Sql\Modifier;

require_once __DIR__ . '/class-count.php';

/**
 * Modifies an SQL query
 */
class Modifier {
	/**
	 * Get the SQL for an array of queries
	 *
	 * @param array $queries Queries.
	 * @return array
	 */
	protected function get_queries( array $queries ) {
		$queries = array_filter(
			array_map(
				function( $query ) {
					if ( $query ) {
						return $query->get_as_sql();
					}

					return false;
				},
				$queries
			)
		);

		return array_values( $queries );
	}

	/**
	 * Get the JOIN for the columns
	 *
	 * @param array  $joins SQL joins.
	 * @param string $column Column name.
	 * @return array
	 */
	protected function get_joins( array $joins, $column ) {
		return $this->get_queries(
			array_map(
				function( $join ) use ( $column ) {
					return $join->$column();
				},
				$joins
			)
		);
	}

	/**
	 * Remove any joined column from the select list
	 *
	 * @param array $items Items.
	 * @param array $joins Joins.
	 * @return array
	 */
	protected function remove_join_columns( array $items, array $joins ) {
		return array_filter( $items, function( $item ) use ( $joins ) {
			foreach ( $joins as $join ) {
				if ( $item->is_column_match( $join->get_column() ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	/**
	 * Update column name of any joined column
	 *
	 * @param array $items Items.
	 * @param array $joins Joins.
	 * @return array
	 */
	protected function replace_join_columns( array $items, array $joins ) {
		foreach ( $joins as $join ) {
			foreach ( $items as $item ) {
				$item->update_column( $join->get_column(), $join->get_join_column() );
			}
		}

		return $items;
	}

	/**
	 * Get modified selects
	 *
	 * @param array $select Select items.
	 * @param array $joins Joins.
	 * @return array
	 */
	public function get_select( array $select, array $joins ) {
		$selects = $this->remove_join_columns( $select, $joins );
		$join_selects = $this->get_joins( $joins, 'get_select' );

		if ( count( $join_selects ) > 0 ) {
			// With a join we need to prefix all columns with the table name to avoid SQL ambiquity
			array_walk( $selects, function( $item ) {
				$item->set_prefix_required();
			} );
		}

		return array_merge( $this->get_queries( $selects ), $join_selects );
	}

	/**
	 * Get modified WHERE
	 *
	 * @param array $where Where items.
	 * @param array $joins Joins.
	 * @return array
	 */
	public function get_where( array $where, array $joins ) {
		$where_columns = $this->replace_join_columns( $where, $joins );
		$queries = $this->get_queries( $where_columns );

		return $queries;
	}

	/**
	 * Get modified FROM
	 *
	 * @param array $from From items.
	 * @param array $joins Joins.
	 * @return array
	 */
	public function get_from( array $from, array $joins ) {
		return array_merge( $this->get_queries( $from ), $this->get_joins( $joins, 'get_from' ) );
	}

	/**
	 * Get modified GROUP
	 *
	 * @param array $group Group items.
	 * @param array $joins Joins.
	 * @return array
	 */
	public function get_group( array $group, array $joins ) {
		return array_merge( $this->get_queries( $group ), $this->get_joins( $joins, 'get_group' ) );
	}
}
