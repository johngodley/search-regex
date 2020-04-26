<?php

namespace SearchRegex;

/**
 * Contains all information for a search result - a database row that contains matches
 */
class Result {
	private $row_id;
	private $source_type;
	private $source_name;
	private $result_title;
	private $columns;
	private $raw;
	private $actions = [];

	/**
	 * Create the result given a row ID, the Search_Source, a set of columns, and the raw database data.
	 *
	 * @param Integer       $row_id Database row ID.
	 * @param Search_Source $source The search source.
	 * @param Array         $columns Array of Match_Column objects.
	 * @param Array         $raw Raw row data.
	 */
	public function __construct( $row_id, Search_Source $source, array $columns, $raw ) {
		global $wpdb;

		$this->row_id = $row_id;
		$this->columns = $columns;
		$this->raw = $raw;
		$this->source_type = $source->get_type( $raw );
		$this->source_name = $source->get_name( $raw );
		$this->result_title = isset( $raw[ $source->get_title_column() ] ) ? $raw[ $source->get_title_column() ] : false;
		$this->actions = apply_filters( 'searchregex_result_actions', $source->get_actions( $this ), $this->source_type, $this );
	}

	/**
	 * Convert the Result to JSON
	 *
	 * @return Array JSON
	 */
	public function to_json() {
		$columns = [];

		foreach ( $this->columns as $column ) {
			$columns[] = $column->to_json();
		}

		return [
			'row_id' => $this->row_id,
			'source_type' => $this->source_type,
			'source_name' => $this->source_name,
			'columns' => $columns,
			'actions' => $this->actions,
			'title' => $this->result_title,
			'match_count' => \array_reduce( $columns, function( $carry, $column ) {
				return $carry + $column['match_count'];
			}, 0 ),
		];
	}

	/**
	 * Get the Match_Column array
	 *
	 * @return Array Match_Column array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Get the raw data
	 *
	 * @return String Raw data
	 */
	public function get_raw() {
		return $this->raw;
	}

	/**
	 * Get the row ID
	 *
	 * @return Integer Row ID
	 */
	public function get_row_id() {
		return $this->row_id;
	}
}
