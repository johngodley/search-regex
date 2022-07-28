<?php

namespace SearchRegex\Sql\Where;

class Where_Or extends Where {
	/**
	 * Array of WHERE objects that will be ORd together
	 *
	 * @readonly
	 * @var array<Where>
	 */
	protected $wheres = [];

	/**
	 * Constructor
	 *
	 * @param array<Where> $wheres Wheres.
	 */
	public function __construct( array $wheres ) {
		$this->wheres = $wheres;
	}

	/**
	 * Get the WHEREs as a group
	 *
	 * @param string $logic Logic.
	 * @return string
	 */
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
