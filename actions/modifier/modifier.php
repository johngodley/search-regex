<?php

namespace SearchRegex;

/**
 * Modify a column
 */
abstract class Modifier {
	/**
	 * Operation to perform
	 *
	 * @var string|null
	 */
	protected $operation = null;

	/**
	 * Schema
	 *
	 * @var Schema_Column
	 */
	protected $schema;

	/**
	 * Constructor
	 *
	 * @param array         $option Modification options.
	 * @param Schema_Column $schema Schema.
	 */
	public function __construct( array $option, Schema_Column $schema ) {
		$this->schema = $schema;
		$this->operation = '';
	}

	/**
	 * The schema source name
	 *
	 * @return string
	 */
	public function get_source_name() {
		return $this->schema->get_source();
	}

	/**
	 * Get schema column name.
	 *
	 * @return string
	 */
	public function get_column_name() {
		return $this->schema->get_column();
	}

	/**
	 * Get schema
	 *
	 * @return Schema_Column
	 */
	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Does the column match?
	 *
	 * @param string $column Column.
	 * @return boolean
	 */
	public function is_for_column( $column ) {
		return $this->get_column_name() === $column;
	}

	/**
	 * Get the data for this column
	 *
	 * @param array $row Array of database columns.
	 * @return string|false
	 */
	public function get_row_data( array $row ) {
		if ( isset( $row[ $this->get_column_name() ] ) ) {
			return $row[ $this->get_column_name() ];
		}

		return false;
	}

	/**
	 * Create a column modifier
	 *
	 * @param array         $option Options.
	 * @param Schema_Source $schema Schema.
	 * @return Modifier|null
	 */
	public static function create( $option, Schema_Source $schema ) {
		$column = $schema->get_column( isset( $option['column'] ) ? $option['column'] : '' );
		if ( ! $column ) {
			return null;
		}

		if ( $column->get_type() === 'integer' ) {
			return new Modify_Integer( $option, $column );
		}

		if ( $column->get_type() === 'string' ) {
			return new Modify_String( $option, $column );
		}

		if ( $column->get_type() === 'date' ) {
			return new Modify_Date( $option, $column );
		}

		if ( $column->get_type() === 'member' ) {
			return new Modify_Member( $option, $column );
		}

		if ( $column->get_type() === 'keyvalue' ) {
			return new Modify_Keyvalue( $option, $column );
		}

		return null;
	}

	/**
	 * Is this modifier valid?
	 *
	 * @return boolean
	 */
	public function is_valid() {
		return $this->operation !== null;
	}

	/**
	 * Get changes for this modifier and value
	 *
	 * @param string $value Value.
	 * @return array|null
	 */
	public function get_change( $value ) {
		return null;
	}

	/**
	 * Convert the modifier to JSON
	 *
	 * @return array
	 */
	public function to_json() {
		return [
			'column' => $this->get_column_name(),
			'source' => $this->get_source_name(),
		];
	}

	/**
	 * Perform the modifier on a column
	 *
	 * @param integer       $row_id Row ID.
	 * @param string        $row_value Row value.
	 * @param Search_Source $source Source.
	 * @param Match_Column  $column Column.
	 * @param array         $raw Raw database data.
	 * @return Match_Column
	 */
	abstract public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw );
}
