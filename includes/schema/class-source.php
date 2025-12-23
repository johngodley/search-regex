<?php

namespace SearchRegex\Schema;

/**
 * Helper to represent the schema for a source
 *
 * @phpstan-type SourceSchemaJson array{
 *   type?: string,
 *   table?: string,
 *   columns?: list<array<string, mixed>>
 * }
 */
class Source {
	/**
	 * Source type
	 */
	private string $type = '';

	/**
	 * Source table
	 */
	private string $table = '';

	/**
	 * Array of Column objects
	 *
	 * @var Column[]
	 */
	private array $columns = [];

	/**
	 * Constructor
	 *
	 * @param SourceSchemaJson $schema_json JSON.
	 */
	public function __construct( $schema_json ) {
		if ( isset( $schema_json['type'] ) ) {
			$this->type = $schema_json['type'];
		}

		if ( isset( $schema_json['table'] ) ) {
			$this->table = $schema_json['table'];
		}

		// @phpstan-ignore booleanAnd.rightAlwaysTrue
		if ( isset( $schema_json['columns'] ) && is_array( $schema_json['columns'] ) ) {
			foreach ( $schema_json['columns'] as $column ) {
				if ( isset( $column['column'] ) ) {
					$this->columns[ $column['column'] ] = new Column( $column, $this );
				}
			}
		}
	}

	/**
	 * Get Column for a column
	 *
	 * @param string $column_name Name of column.
	 * @return Column|null
	 */
	public function get_column( $column_name ) {
		return $this->columns[ $column_name ] ?? null;
	}

	/**
	 * Get all columns
	 *
	 * @return array<Column>
	 */
	public function get_columns() {
		return array_values( $this->columns );
	}

	/**
	 * Get all global columns
	 *
	 * @return array<Column>
	 */
	public function get_global_columns() {
		return array_filter(
			$this->columns, fn( $column ) => $column->is_global()
		);
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table;
	}

	/**
	 * Get type of source
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}
}
