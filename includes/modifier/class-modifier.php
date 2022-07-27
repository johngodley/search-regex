<?php

namespace SearchRegex\Modifier;

use SearchRegex\Schema;
use SearchRegex\Modifier\Value;
use SearchRegex\Search;
use SearchRegex\Source;

require_once __DIR__ . '/modifier-string.php';
require_once __DIR__ . '/modifier-member.php';
require_once __DIR__ . '/modifier-integer.php';
require_once __DIR__ . '/modifier-date.php';
require_once __DIR__ . '/modifier-keyvalue.php';

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
	 * @var Schema\Column
	 */
	protected $schema;

	/**
	 * Constructor
	 *
	 * @param array         $option Modification options.
	 * @param Schema\Column $schema Schema.
	 */
	public function __construct( array $option, Schema\Column $schema ) {
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
	 * @return Schema\Column
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
	 * @param Schema\Source $schema Schema.
	 * @return Modifier|null
	 */
	public static function create( $option, Schema\Source $schema ) {
		$column = $schema->get_column( isset( $option['column'] ) ? $option['column'] : '' );
		if ( ! $column ) {
			return null;
		}

		if ( $column->get_type() === 'integer' ) {
			return new Value\Integer_Value( $option, $column );
		}

		if ( $column->get_type() === 'string' ) {
			return new Value\String_Value( $option, $column );
		}

		if ( $column->get_type() === 'date' ) {
			return new Value\Date_Value( $option, $column );
		}

		if ( $column->get_type() === 'member' ) {
			return new Value\Member_Value( $option, $column );
		}

		if ( $column->get_type() === 'keyvalue' ) {
			return new Value\Key_Value( $option, $column );
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
	 * @param Source\Source $source Source.
	 * @param Search\Column $column Column.
	 * @param array         $raw Raw database data.
	 * @param boolean       $save_mode Is the save mode enabled.
	 * @return Search\Column
	 */
	abstract public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode );
}
