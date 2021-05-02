<?php

namespace SearchRegex\Sql;

class Sql_Modifier {
	protected function get_queries( $queries ) {
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

	protected function get_joins( $joins, $column ) {
		return $this->get_queries(
			array_map(
				function( $join ) use ( $column ) {
					return $join->$column();
				},
				$joins
			)
		);
	}

	// Remove any joined column from the select list
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

	// Update column name of any joined column
	protected function replace_join_columns( array $items, array $joins ) {
		foreach ( $joins as $join ) {
			foreach ( $items as $pos => $item ) {
				$item->update_column( $join->get_column(), $join->get_join_column() );
			}
		}

		return $items;
	}

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

	public function get_where( array $where, array $joins ) {
		$where_columns = $this->replace_join_columns( $where, $joins );
		$queries = $this->get_queries( $where_columns );

		return $queries;
	}

	public function get_from( array $from, array $joins ) {
		return array_merge( $this->get_queries( $from ), $this->get_joins( $joins, 'get_from' ) );
	}

	public function get_group( array $group, array $joins ) {
		return array_merge( $this->get_queries( $group ), $this->get_joins( $joins, 'get_group' ) );
	}
}

require_once __DIR__ . '/count.php';
