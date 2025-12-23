<?php

namespace SearchRegex\Search;

use SearchRegex\Source;
use SearchRegex\Search;

/**
 * Contains all information for a search result - a database row that contains matches
 *
 * @phpstan-type ResultJson array{
 *   row_id: int,
 *   source_type: string,
 *   source_name: string,
 *   columns: array<int, mixed>,
 *   actions: string[],
 *   title: string,
 *   match_count: int
 * }
 *
 * @phpstan-type ResultUpdates array<string, array{
 *   change: mixed,
 *   same: mixed
 * }>
 */
class Result {
	/**
	 * Row ID
	 */
	private int $row_id;

	/**
	 * Source type
	 */
	private string $source_type;

	/**
	 * Source name
	 */
	private string $source_name;

	/**
	 * A title for the result. e.g. post title
	 *
	 * @var string|false
	 */
	private $result_title;

	/**
	 * Array of columns with matches
	 *
	 * @var Search\Column[]
	 */
	private array $columns;

	/**
	 * Raw data for this result
	 *
	 * @var string[]
	 */
	private array $raw;

	/**
	 * Array of actions that can be performed on this result
	 *
	 * @var string[]
	 */
	private array $actions = [];

	/**
	 * Create the result given a row ID, the Source\Source, a set of columns, and the raw database data.
	 *
	 * @param int $row_id Database row ID.
	 * @param Source\Source $source The search source.
	 * @param Search\Column[] $columns Array of Search\Column objects.
	 * @param array<string, mixed> $raw Raw row data.
	 */
	public function __construct( $row_id, Source\Source $source, array $columns, array $raw ) {
		$this->row_id = $row_id;
		$this->columns = $columns;
		$this->raw = $raw;
		$this->source_type = $source->get_type( $raw );
		$this->source_name = $source->get_name( $raw );
		$this->result_title = $raw[ $source->get_title_column() ] ?? false;
		$this->actions = \apply_filters( 'searchregex_result_actions', $source->get_actions( $this ), $this->source_type, $this );

		// Get columns as positional values
		$schema = $source->get_schema_order();

		usort(
			$this->columns, function ( $a, $b ) use ( $schema ) {
					$a = $schema[ $a->get_column_id() ];
					$b = $schema[ $b->get_column_id() ];
					return $a <=> $b;
			}
		);
	}

	/**
	 * Convert the Result to JSON
	 *
	 * @return ResultJson JSON
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
			'title' => $this->result_title !== false ? html_entity_decode( $this->result_title ) : '',

			'match_count' => \array_reduce(
				$columns, fn( $carry, $column ) => $carry + $column['match_count'], 0
			),
		];
	}

	/**
	 * Get the Search\Column array
	 *
	 * @return Search\Column[] Array of Search\Column objects
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Get the raw data
	 *
	 * @return string[] Raw data
	 */
	public function get_raw() {
		return $this->raw;
	}

	/**
	 * Get the row ID
	 *
	 * @return int Row ID
	 */
	public function get_row_id() {
		return $this->row_id;
	}

	/**
	 * Get the result source type
	 *
	 * @return string
	 */
	public function get_source_type() {
		return $this->source_type;
	}

	/**
	 * Get array of changes for this result
	 *
	 * @return ResultUpdates
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
