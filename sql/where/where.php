<?php

namespace SearchRegex\Sql;

/**
 * SQL WHERE
 */
class Sql_Where {
	/**
	 * Column
	 *
	 * @readonly
	 * @var Sql_Select|null
	 */
	protected $column = null;

	/**
	 * WHERE logic
	 *
	 * @readonly
	 * @var string
	 */
	protected $logic = '=';

	/**
	 * WHERE value
	 *
	 * @readonly
	 * @var string|integer|array
	 */
	protected $value = '';

	/**
	 * Constructor
	 *
	 * @param Sql_Select           $column Column.
	 * @param string               $logic Logic.
	 * @param string|integer|array $value Value.
	 */
	protected function __construct( Sql_Select $column, $logic, $value = '' ) {
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

require_once __DIR__ . '/or.php';
require_once __DIR__ . '/and.php';
require_once __DIR__ . '/integer.php';
require_once __DIR__ . '/string.php';
require_once __DIR__ . '/date.php';
require_once __DIR__ . '/in.php';
require_once __DIR__ . '/null.php';
