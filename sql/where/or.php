<?php

namespace SearchRegex\Sql;

class Sql_Where_Or extends Sql_Where {
	protected $wheres = [];

	public function __construct( array $wheres ) {
		$this->wheres = $wheres;
	}

	protected function get_group( $logic ) {
		$start = '';
		$end = '';

		if ( count( $this->wheres ) > 1 ) {
			$start = '(';
			$end = ')';
		}

		$sql = array_map( function( $where ) {
			return $where->get_as_sql();
		}, $this->wheres );

		return $start . implode( ' ' . $logic . ' ', $sql ) . $end;
	}

	public function get_as_sql() {
		return $this->get_group( 'OR' );
	}

	public function update_column( $column, $updated_column ) {
		foreach ( $this->wheres as $where ) {
			$where->update_column( $column, $updated_column );
		}
	}
}
