<?php

namespace SearchRegex;

/**
 * Contains all information for a search result - a database row that contains matches
 */
class Result {
	/**
	 * Row ID
	 *
	 * @var Int
	 **/
	private $row_id;

	/**
	 * Source type
	 *
	 * @var String
	 **/
	private $source_type;

	/**
	 * Source name
	 *
	 * @var String
	 **/
	private $source_name;

	/**
	 * A title for the result. e.g. post title
	 *
	 * @var String
	 **/
	private $result_title;

	/**
	 * Array of columns with matches
	 *
	 * @var Match_Column[]
	 **/
	private $columns;

	/**
	 * Raw data for this result
	 *
	 * @var String[]
	 **/
	private $raw;

	/**
	 * Array of actions that can be performed on this result
	 *
	 * @var String[]
	 **/
	private $actions = [];

	/**
	 * Create the result given a row ID, the Search_Source, a set of columns, and the raw database data.
	 *
	 * @param Int           $row_id Database row ID.
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
		/** @psalm-suppress TooManyArguments */
		$this->actions = \apply_filters( 'searchregex_result_actions', $source->get_actions( $this ), $this->source_type, $this );
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
			'title' => html_entity_decode( $this->result_title ),
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
	 * @return String[] Raw data
	 */
	public function get_raw() {
		return $this->raw;
	}

	/**
	 * Get the row ID
	 *
	 * @return Int Row ID
	 */
	public function get_row_id() {
		return $this->row_id;
	}

	/**
	 * Get the result source type
	 *
	 * @return String
	 */
	public function get_source_type() {
		return $this->source_type;
	}
}
