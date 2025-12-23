<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

/**
 * SQL WHERE
 */
class Where {
	/**
	 * Column
	 *
	 * @readonly
	 */
	protected ?Sql\Select\Select $column;

	/**
	 * WHERE logic
	 *
	 * @readonly
	 */
	protected string $logic;

	/**
	 * WHERE value
	 *
	 * @readonly
	 * @var string|integer|list<string|int>
	 */
	protected $value;

	/**
	 * Constructor
	 *
	 * @param Sql\Select\Select    $column Column.
	 * @param string               $logic Logic.
	 * @param string|integer|list<string|int> $value Value.
	 */
	protected function __construct( Sql\Select\Select $column, $logic, $value = '' ) {
		$this->column = $column;
		$this->value = $value;  // Sanitized in get_value
		$this->logic = $logic;
	}

	/**
	 * Get as SQL
	 *
	 * @return string
	 */
	public function get_as_sql() {
		if ( $this->column !== null ) {
			return $this->column->get_column_or_alias() . ' ' . $this->logic . ' ' . $this->get_value();
		}

		return '';
	}

	/**
	 * Change the column
	 *
	 * @param string $column Column.
	 * @param string $updated_column New column.
	 * @return void
	 */
	public function update_column( $column, $updated_column ) {
		if ( $this->column !== null ) {
			$this->column->update_column( $column, $updated_column );
		}
	}

	/**
	 * Get the WHERE value
	 *
	 * @return string
	 */
	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%s', $this->value );
	}
}
