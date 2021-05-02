<?php

namespace SearchRegex\Sql;

class Sql_Select {
	protected $column = null;
	protected $alias = null;
	protected $table = null;
	protected $prefix_sql = false;

	public function __construct( Sql_Value $table, Sql_Value $column, Sql_Value $alias = null, $prefix_required = false ) {
		$this->table = $table->get_value();
		$this->column = $column->get_value();
		$this->alias = $alias ? $alias->get_value() : null;
		$this->prefix_sql = $prefix_required;
	}

	public function get_as_sql() {
		$sql = $this->column;

		if ( $this->prefix_sql && $this->table ) {
			$sql = $this->table . '.' . $sql;
		}

		if ( $this->alias && $this->alias !== $this->column ) {
			$sql .= ' AS ' . $this->alias;
		}

		return $sql;
	}

	public function get_column_or_alias() {
		if ( $this->alias ) {
			return $this->alias . '.' . $this->column;;
		}

		if ( $this->table ) {
			return $this->table . '.' . $this->column;
		}

		return $this->column;
	}

	public function update_column( $column, $updated_column ) {
		if ( $this->is_column_match( $column ) ) {
			$this->column = $updated_column;
			$this->table = null;
		}
	}

	public function is_column_match( $column ) {
		return $this->column === $column;
	}

	public function to_upper() {
		$this->column = 'UPPER(' . $this->column . ')';
	}

	public function set_prefix_required() {
		$this->prefix_sql = true;
	}
}

require_once __DIR__ . '/select-column.php';
require_once __DIR__ . '/sum.php';
