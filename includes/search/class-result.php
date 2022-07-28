<?php

namespace SearchRegex\Search;

use SearchRegex\Source;
use SearchRegex\Search;

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
	 * @var Search\Column[]
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
	 * Create the result given a row ID, the Source\Source, a set of columns, and the raw database data.
	 *
	 * @param Int           $row_id Database row ID.
	 * @param Source\Source $source The search source.
	 * @param Array         $columns Array of Search\Column objects.
	 * @param Array         $raw Raw row data.
	 */
	public function __construct( $row_id, Source\Source $source, array $columns, $raw ) {
		$this->row_id = $row_id;
		$this->columns = $columns;
		$this->raw = $raw;
		$this->source_type = $source->get_type( $raw );
		$this->source_name = $source->get_name( $raw );
		$this->result_title = isset( $raw[ $source->get_title_column() ] ) ? $raw[ $source->get_title_column() ] : false;
		/** @psalm-suppress TooManyArguments */
		$this->actions = \apply_filters( 'searchregex_result_actions', $source->get_actions( $this ), $this->source_type, $this );

		// Get columns as positional values
		$schema = $source->get_schema_order();

		usort( $this->columns, function( $a, $b ) use ( $schema ) {
			$a = $schema[ $a->get_column_id() ];
			$b = $schema[ $b->get_column_id() ];

			if ( $a === $b ) {
				return 0;
			}

			return $a < $b ? -1 : 1;
		} );
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
	 * Get the Search\Column array
	 *
	 * @return Array Search\Column array
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

	/**
	 * Get array of changes for this result
	 *
	 * @return array
	 */
	public function get_updates() {
		$updates = [];

		foreach ( $this->columns as $column ) {
			$change = $column->get_changes( $this->raw );
			$same = $column->get_same( $this->raw );

			if ( count( $change ) > 0 ) {
				$updates[ $column->get_column_id() ] = [
					'change' => $change,
					'same' => $same,
				];
			}
		}

		return $updates;
	}
}
