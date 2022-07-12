<?php

namespace SearchRegex\Schema;

/**
 * Helper to represent the schema for a source
 */
class Source {
	/**
	 * Source type
	 *
	 * @var string
	 */
	private $type = '';

	/**
	 * Source label
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * Source table
	 *
	 * @var string
	 */
	private $table = '';

	/**
	 * Array of Column objects
	 *
	 * @var Column[]
	 */
	private $columns = [];

	/**
	 * Constructor
	 *
	 * @param array $schema_json JSON.
	 */
	public function __construct( array $schema_json ) {
		if ( isset( $schema_json['type'] ) ) {
			$this->type = $schema_json['type'];
		}

		if ( isset( $schema_json['name'] ) ) {
			$this->name = $schema_json['name'];
		}

		if ( isset( $schema_json['table'] ) ) {
			$this->table = $schema_json['table'];
		}

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
		if ( isset( $this->columns[ $column_name ] ) ) {
			return $this->columns[ $column_name ];
		}

		return null;
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
		return array_filter( $this->columns, function( $column ) {
			return $column->is_global();
		} );
	}

	/**
	 * Get all joined columns
	 *
	 * @return array<Column>
	 */
	public function get_join_columns() {
		return array_filter(
			$this->columns,
			/** @psalm-suppress all */
			function( Column $column ) {
				return $column->get_join_column();
			}
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
