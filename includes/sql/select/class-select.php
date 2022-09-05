<?php

namespace SearchRegex\Sql\Select;

use SearchRegex\Sql;

require_once __DIR__ . '/class-column.php';

/**
 * SQL SELECT
 */
class Select {
	/**
	 * Column name
	 *
	 * @var string
	 */
	protected $column;

	/**
	 * Column alias
	 *
	 * @readonly
	 * @var string|null
	 */
	protected $alias = null;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * SQL prefix
	 *
	 * @var boolean
	 */
	protected $prefix_sql = false;

	/**
	 * Constructor
	 *
	 * @param Sql\Value      $table Table name.
	 * @param Sql\Value      $column Column name.
	 * @param Sql\Value|null $alias Table alias.
	 * @param boolean        $prefix_required Whether we need to prefix the SQL with the table name.
	 */
	public function __construct( Sql\Value $table, Sql\Value $column, Sql\Value $alias = null, $prefix_required = false ) {
		$this->table = $table->get_value();
		$this->column = $column->get_value();
		$this->alias = $alias ? $alias->get_value() : null;
		$this->prefix_sql = $prefix_required;
	}

	/**
	 * Get as SQL
	 *
	 * @return string
	 */
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

	/**
	 * Get the column or aliased column
	 *
	 * @return string
	 */
	public function get_column_or_alias() {
		if ( $this->alias ) {
			return $this->alias . '.' . $this->column;
		}

		if ( $this->table ) {
			return $this->table . '.' . $this->column;
		}

		return $this->column;
	}

	/**
	 * Update the column
	 *
	 * @param string $column Column.
	 * @param string $updated_column Updated column.
	 * @return void
	 */
	public function update_column( $column, $updated_column ) {
		if ( $this->is_column_match( $column ) ) {
			$this->column = $updated_column;
			$this->table = '';
		}
	}

	/**
	 * Does this match the column?
	 *
	 * @param string $column Column to match.
	 * @return boolean
	 */
	public function is_column_match( $column ) {
		return $this->column === $column;
	}

	/**
	 * Mark that we need the column to be prefixed with table name
	 *
	 * @return void
	 */
	public function set_prefix_required() {
		$this->prefix_sql = true;
	}
}
