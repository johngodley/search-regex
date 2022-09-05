<?php

namespace SearchRegex\Search;

use SearchRegex\Action;
use SearchRegex\Filter;
use SearchRegex\Source;

require_once __DIR__ . '/class-match-text.php';
require_once __DIR__ . '/class-match-column.php';
require_once __DIR__ . '/class-flags.php';
require_once __DIR__ . '/class-totals.php';
require_once __DIR__ . '/class-preset.php';
require_once __DIR__ . '/class-result.php';

/**
 * Perform a search
 */
class Search {
	/**
	 * The sources to search across.
	 *
	 * @var list<Source\Source>
	 **/
	private $sources = [];

	/**
	 * Create a Search object, with a search value, an array of sources, and some search flags
	 *
	 * @param Array $sources Array of Source\Source objects. Only one is supported.
	 */
	public function __construct( array $sources ) {
		$this->sources = $sources;
	}

	/**
	 * Get a single database row
	 *
	 * @param integer       $row_id Row ID to return.
	 * @param Action\Action $action Action.
	 * @return \WP_Error|Array Return a single database row, or \WP_Error on error
	 */
	public function get_row( $row_id, Action\Action $action ) {
		$results = $this->sources[0]->get_row( $row_id );

		// Error
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		return $this->convert_rows_to_results( [
			[
				'results' => $results,
				'source_pos' => 0,
			],
		], $action );
	}

	/**
	 * Perform the search, returning a result array that contains the totals, the progress, and an array of Result objects
	 *
	 * @param Action\Action $action The action to perform on the search.
	 * @param int           $offset Current page offset.
	 * @param int           $per_page Per page limit.
	 * @param int           $limit Max number of results.
	 * @return Array|\WP_Error Array containing `totals`, `progress`, and `results`
	 */
	public function get_search_results( Action\Action $action, $offset, $per_page, $limit = 0 ) {
		$totals = new Totals();

		// Get total results
		$result = $totals->get_totals( $this->sources );
		if ( $result instanceof \WP_Error ) {
			return $result;
		}

		// Get the data
		$rows = $this->get_search_data( $offset, $per_page, $totals );
		if ( $rows instanceof \WP_Error ) {
			return $rows;
		}

		// Convert it to Results, performing any action along the way
		$results = $this->convert_rows_to_results( $rows, $action );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		// Calculate the prev/next pages of results
		$previous = max( 0, $offset - $per_page );

		if ( $previous === $offset ) {
			$previous = false;
		}

		list( $next, $results ) = $this->get_next_results( $totals, $offset, $per_page, $limit, $results );

		return [
			'results' => $action->should_save() ? [] : $results,
			'totals' => $totals->to_json(),
			'progress' => [
				'current' => $offset,
				'rows' => count( $results ),
				'previous' => $previous,
				'next' => $next,
			],
		];
	}

	/**
	 * Get the results and next position. For advanced searches this may be a smaller set if we have a limit value
	 *
	 * @param Totals $totals Totals.
	 * @param int    $offset Current page offset.
	 * @param int    $per_page Per page limit.
	 * @param int    $limit Max number of results.
	 * @param Array  $results Result array.
	 * @return Array
	 */
	private function get_next_results( $totals, $offset, $per_page, $limit, $results ) {
		$next = $totals->get_next_page( $offset + $per_page );

		// We always go in $per_page groups, but we need to limit if we only need a few more to fill a result set
		if ( $limit > 0 && $limit < count( $results ) ) {
			$new_results = [];
			$new_offset = $offset;

			foreach ( $results as $result ) {
				if ( $result ) {
					$new_results[] = $result;
				}

				$new_offset++;

				if ( count( $new_results ) === $limit ) {
					break;
				}
			}

			$results = $new_results;
			$next = min( $new_offset, $next );
		} else {
			$results = array_filter( $results, function( $item ) {
				return $item !== false;
			} );
		}

		if ( $next === $offset ) {
			$next = false;
		}

		return [
			$next,
			$results,
		];
	}

	/**
	 * Get totals for source
	 *
	 * @param Totals        $totals Totals.
	 * @param Source\Source $source Source.
	 * @return integer
	 */
	protected function get_total_for_source( Totals $totals, Source\Source $source ) {
		return $totals->get_total_rows_for_source( $source->get_type() );
	}

	/**
	 * Get the match data for a search. We merge all result sets for all sources together, and then paginate across them.
	 *
	 * @param Int    $absolute_offset Absolute page offset across all sources (assuming they don't change).
	 * @param Int    $limit Page limit.
	 * @param Totals $totals Source totals.
	 * @return \WP_Error|Array Data array
	 */
	public function get_search_data( $absolute_offset, $limit, Totals $totals ) {
		$results = [];
		$current_offset = 0;
		$remaining_limit = $limit;

		// Go through each row and see if our $absolute_offset + $limit is within it's result set
		foreach ( $this->sources as $source_pos => $source ) {
			// Get total number of rows for this source
			$num_rows = $totals->get_matched_rows_for_source( $source->get_type() );

			// Are we within the correct result set?
			if ( $num_rows > 0 && $current_offset + $num_rows >= $absolute_offset ) {
				// Adjust for the current source offset
				$source_offset = max( 0, $absolute_offset - $current_offset );

				// Read up to our remaining limit, or the remaining number of rows
				$source_limit = min( $remaining_limit, $num_rows - $source_offset );
				$source_results = $source->get_matched_rows( $source_offset, $source_limit );

				// Check for an error
				if ( $source_results instanceof \WP_Error ) {
					return $source_results;
				}

				// Subtract the rows we've read from this source. There could be rows in another source to read
				$remaining_limit -= $source_limit;
				$current_offset = $source_offset + count( $source_results );

				// Append to merged set
				$results[] = [
					'source_pos' => $source_pos,
					'results' => $source_results,
				];

				if ( $remaining_limit <= 0 ) {
					break;
				}
			} else {
				// Move on to the next absolute offset
				$current_offset += $num_rows;
			}
		}

		return $results;
	}

	/**
	 * Convert database rows into Result objects
	 *
	 * @internal
	 * @param array         $source_results Array of row data.
	 * @param Action\Action $action Action\Action object.
	 * @return Result[]|\WP_Error Array of results
	 */
	public function convert_rows_to_results( array $source_results, Action\Action $action ) {
		$results = [];

		// Loop over the source results, extracting the source and results for that source
		foreach ( $source_results as $result ) {
			$source = $this->sources[ $result['source_pos'] ];
			$rows = $result['results'];

			// Loop over the results for the source
			foreach ( $rows as $row ) {
				$result = $this->convert_search_results( $action, $row, $source );

				if ( $result instanceof \WP_Error ) {
					return $result;
				}

				if ( $result ) {
					if ( $action->should_save() ) {
						$saved = $this->save_changes( $result );

						if ( $saved instanceof \WP_Error ) {
							return $saved;
						}
					}
				}

				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * Convert a database row into a Result, after performing any action
	 *
	 * @param Action\Action $action Action\Action.
	 * @param array         $row Data.
	 * @param Source\Source $source Source.
	 * @return Result|\WP_Error|false
	 */
	private function convert_search_results( Action\Action $action, $row, $source ) {
		// Get the matches
		$matches = Filter\Filter::get_result_matches( $source, $row, $action );
		$row_id = intval( array_values( $row )[0], 10 );

		// Perform the actions, if we are saving
		$matches = $action->perform( $row_id, $row, $source, $matches );
		if ( $matches instanceof \WP_Error ) {
			return $matches;
		}

		if ( count( $matches ) > 0 ) {
			return new Result( $row_id, $source, $matches, $row );
		}

		if ( count( $source->get_filters() ) === 0 ) {
			// No filters - just return the row as-is
			return new Result( $row_id, $source, [], $row );
		}

		return false;
	}

	/**
	 * Save a set of changes on results.
	 *
	 * @param Result $result Result.
	 * @return boolean|\WP_Error
	 */
	public function save_changes( Result $result ) {
		foreach ( $this->sources as $source ) {
			if ( $source->is_type( $result->get_source_type() ) ) {
				$updates = $result->get_updates();

				if ( count( $updates ) > 0 ) {
					return $source->save( $result->get_row_id(), $updates );
				}
			}
		}

		// No changes
		return false;
	}
}
