<?php

namespace SearchRegex\Sql;

/**
 * A simple sanitizer for table names, column names, and raw (pre-sanitized) names. This shouldn't be treated as a replacement for $wpdb->prepare, and is just
 * a way of being extra-paranoid when forming queries with known column and table names.
 */
class Value {
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
	 * Create a Value with a known sanitized value. You should only use this when you are sure the value is safe.
	 *
	 * @param string $value Value.
	 * @return Value
	 */
	public static function safe_raw( $value ) {
		return new Value( $value );
	}

	/**
	 * Create a Value for a SQL column. Performs column sanitization and allows for column aliases
	 *
	 * @param string $column Column name.
	 * @return Value
	 */
	public static function column( $column ) {
		$column = preg_replace( '/[^ A-Za-z0-9_\-\.]/', '', $column );

		return new Value( $column );
	}

	/**
	 * Create a Value for a SQL table name. Performs table name sanitization.
	 *
	 * @param string $table Table name.
	 * @return Value
	 */
	public static function table( $table ) {
		$table = preg_replace( '/[^A-Za-z0-9_\-]/', '', $table );

		return new Value( $table );
	}
}
