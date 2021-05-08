<?php

namespace SearchRegex\Sql;

/**
 * A simple sanitizer for table names, column names, and raw (pre-sanitized) names
 */
class Sql_Value {
	/**
	 * Underlying value
	 *
	 * @readonly
	 * @var string
	 */
	private $value;

	/**
	 * Constructor
	 *
	 * @param string $value Value.
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * Get the sanitized value.
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Create a Sql_Value with a known sanitized value.
	 *
	 * @param string $value Value.
	 * @return Sql_Value
	 */
	public static function raw( $value ) {
		return new Sql_Value( $value );
	}

	/**
	 * Create a Sql_Value for a SQL column. Performs column sanitization.
	 *
	 * @param string $column Column name.
	 * @return Sql_Value
	 */
	public static function column( $column ) {
		$column = preg_replace( '/[^ A-Za-z0-9_\-\.]/', '', $column );

		return new Sql_Value( $column );
	}

	/**
	 * Create a Sql_Value for a SQL table name. Performs table name sanitization.
	 *
	 * @param string $table Table name.
	 * @return Sql_Value
	 */
	public static function table( $table ) {
		$table = preg_replace( '/[^A-Za-z0-9_\-]/', '', $table );

		return new Sql_Value( $table );
	}
}
