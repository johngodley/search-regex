<?php

namespace SearchRegex\Schema;

/**
 * Helper to represent the schema for a column
 *
 * @phpstan-type ColumnType 'integer'|'string'|'date'|'member'|'keyvalue'
 * @phpstan-type ColumnOption array{value: string, label: string}
 * @phpstan-type ColumnSchemaJson array{
 *   column?: string,
 *   type?: ColumnType,
 *   global?: bool,
 *   join?: string,
 *   joined_by?: string,
 *   options?: list<ColumnOption>
 * }
 */
class Column {
	/**
	 * Column name
	 */
	private string $column = '';

	/**
	 * Column type
	 *
	 * @var ColumnType
	 */
	private string $type = 'string';

	/**
	 * Is this a global column?
	 */
	private bool $global = false;

	/**
	 * Join column, if any
	 *
	 * @var string
	 */
	private string $join = '';

	/**
	 * Joined by column
	 *
	 * @var string
	 */
	private string $joined_by = '';

	/**
	 * Any options, if this is a member type
	 *
	 * @var list<ColumnOption>
	 */
	private array $options = [];

	/**
	 * Source name for this column
	 */
	private Source $source;

	/**
	 * Constructor
	 *
	 * @param ColumnSchemaJson $schema JSON.
	 * @param Source $source Source.
	 */
	public function __construct( $schema, Source $source ) {
		$this->source = $source;

		if ( isset( $schema['type'] ) ) {
			$this->type = $schema['type'];
		}

		if ( isset( $schema['column'] ) ) {
			$this->column = $schema['column'];
		}

		if ( isset( $schema['global'] ) ) {
			$this->global = $schema['global'];
		}

		if ( isset( $schema['join'] ) ) {
			$this->join = $schema['join'];
		}

		if ( isset( $schema['joined_by'] ) ) {
			$this->joined_by = $schema['joined_by'];
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $schema['options'] ) && is_array( $schema['options'] ) ) {
			$this->options = $schema['options'];
		}
	}

	/**
	 * Get source name
	 *
	 * @return string
	 */
	public function get_source() {
		return $this->source->get_type();
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->source->get_table();
	}

	/**
	 * Get column type
	 *
	 * @return ColumnType
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get column
	 *
	 * @return string
	 */
	public function get_column() {
		return $this->column;
	}

	/**
	 * Get column join
	 *
	 * @return string|null
	 */
	public function get_join_column() {
		return $this->join;
	}

	/**
	 * Get column joined by
	 *
	 * @return string|null
	 */
	public function get_joined_by() {
		return $this->joined_by;
	}

	/**
	 * Get column options
	 *
	 * @return list<ColumnOption>
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get label for a particular column, or false
	 *
	 * @param string $value Value name.
	 * @return string|false
	 */
	public function get_option_label( $value ) {
		$options = $this->get_options();

		if ( $options ) {
			foreach ( $options as $option ) {
				if ( $option['value'] === $value ) {
					return $option['label'];
				}
			}
		}

		return false;
	}

	/**
	 * Is this a global column?
	 *
	 * @return boolean
	 */
	public function is_global() {
		return $this->global;
	}
}
