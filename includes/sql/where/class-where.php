<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Sql;

require_once __DIR__ . '/where-or.php';
require_once __DIR__ . '/where-and.php';
require_once __DIR__ . '/where-integer.php';
require_once __DIR__ . '/where-string.php';
require_once __DIR__ . '/where-date.php';
require_once __DIR__ . '/where-in.php';
require_once __DIR__ . '/where-null.php';

/**
 * SQL WHERE
 */
class Where {
	/**
	 * Column
	 *
	 * @readonly
	 * @var Sql\Select\Select|null
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
	 * @param Sql\Select\Select    $column Column.
	 * @param string               $logic Logic.
	 * @param string|integer|array $value Value.
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
