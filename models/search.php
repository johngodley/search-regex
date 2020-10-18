<?php

namespace SearchRegex;

use SearchRegex\Result_Collection;
use SearchRegex\Match;
use SearchRegex\Totals;

require_once __DIR__ . '/source.php';
require_once __DIR__ . '/source-manager.php';
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/match-context.php';
require_once __DIR__ . '/match-column.php';
require_once __DIR__ . '/search-flags.php';
require_once __DIR__ . '/source-flags.php';
require_once __DIR__ . '/totals.php';
require_once __DIR__ . '/preset.php';

/**
 * Perform a search
 */
class Search {
	/**
	 * The phrase to search for.
	 *
	 * @var String
	 **/
	private $search;

	/**
	 * The sources to search across.
	 *
	 * @var list<Search_Source>
	 **/
	private $sources = [];

	/**
	 * The search flags to use when searching.
	 *
	 * @var Search_Flags
	 **/
	private $flags;

	/**
	 * Create a Search object, with a search value, an array of sources, and some search flags
	 *
	 * @param String       $search_value The value to search for.
	 * @param Array        $sources Array of Search_Source objects. Only one is supported.
	 * @param Search_Flags $flags Search flags.
	 */
	public function __construct( $search_value, array $sources, Search_Flags $flags ) {
		$this->search = $search_value;
		$this->flags = $flags;
		$this->sources = $sources;
	}

	/**
	 * Get a single database row
	 *
	 * @param Search_Source $source Source that contains the row.
	 * @param int           $row_id Row ID to return.
	 * @param Replace       $replacer The Replace object used when replacing data.
	 * @return \WP_Error|Array Return a single database row, or WP_Error on error
	 */
	public function get_row( Search_Source $source, $row_id, Replace $replacer ) {
		global $wpdb;

		$row = $source->get_row( $row_id );

		// Error
		if ( is_wp_error( $row ) ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error );
		}

		$results = [
			[
				'results' => [ $row ],
				'source_pos' => 0,
			],
		];
		return $this->convert_rows_to_results( $results, $replacer );
	}

	/**
	 * Perform the search, returning a result array that contains the totals, the progress, and an array of Result objects
	 *
	 * @param Replace $replacer The replacer which performs any replacements.
	 * @param int     $offset Current page offset.
	 * @param int     $per_page Per page limit.
	 * @param int     $limit Max number of results.
	 * @return Array|\WP_Error Array containing `totals`, `progress`, and `results`
	 */
	public function get_search_results( Replace $replacer, $offset, $per_page, $limit = 0 ) {
		$totals = new Totals();

		// Get total results
		$result = $totals->get_totals( $this->sources, $this->search );
		if ( $result instanceof \WP_Error ) {
			return $result;
		}

		// Get the data
		$rows = $this->get_search_data( $offset, $per_page, $totals );
		if ( $rows instanceof \WP_Error ) {
			return $rows;
		}

		// Convert it to Results
		$results = $this->convert_rows_to_results( (array) $rows, $replacer );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		// Calculate the prev/next pages of results
		$previous = max( 0, $offset - $per_page );
		$next = $totals->get_next_page( $offset + $per_page, $this->flags->is_regex() );

		// We always go in $per_page groups, but we need to limit if we only need a few more to fill a result set
		if ( $limit > 0 && $limit < count( $results ) ) {
			$next = min( $offset + $limit, $next );
			$results = array_slice( $results, 0, $limit === 0 ? $per_page : $limit );
		}

		if ( $next === $offset ) {
			$next = false;
		}

		if ( $previous === $offset ) {
			$previous = false;
		}

		return [
			'results' => $results,
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
		$source_offset = 0;
		$remaining_limit = $limit;

		// Go through each row and see if our $absolute_offset + $limit is within it's result set
		foreach ( $this->sources as $source_pos => $source ) {
			$num_rows = $totals->get_total_for_source( $source->get_type(), $this->flags->is_regex() );

			// Are we within the correct result set?
			if ( $current_offset + $num_rows >= $absolute_offset && $num_rows > 0 ) {
				// Adjust for the current source offset
				$source_offset = max( 0, $absolute_offset - $current_offset );

				// Read up to our remaining limit, or the remaining number of rows
				$source_limit = min( $remaining_limit, $num_rows - $source_offset );

				if ( $this->flags->is_regex() ) {
					$source_results = $source->get_all_rows( $this->search, $source_offset, $source_limit );
				} else {
					$source_results = $source->get_matched_rows( $this->search, $source_offset, $source_limit );
				}

				// Check for an error
				if ( $source_results instanceof \WP_Error ) {
					return $source_results;
				}

				// Subtract the rows we've read from this source. There could be rows in another source to read
				$remaining_limit -= $source_limit;

				// Append to merged set
				$results[] = [
					'source_pos' => $source_pos,
					'results' => $source_results,
				];
			}

			// Move on to the next absolute offset
			$current_offset += $num_rows;

			if ( $remaining_limit <= 0 ) {
				break;
			}
		}

		return $results;
	}

	/**
	 * Perform the search for a global replace, returning a result array that contains the totals, the progress, and an array of Result objects
	 *
	 * @param Replace $replacer The replacer which performs any replacements.
	 * @param String  $offset Current page offset.
	 * @param int     $per_page Per page limit.
	 * @return Array|\WP_Error Array containing `totals`, `progress`, and `results`
	 */
	public function get_replace_results( Replace $replacer, $offset, $per_page ) {
		$totals = new Totals();

		// Get total results
		$result = $totals->get_totals( $this->sources, $this->search );
		if ( $result instanceof \WP_Error ) {
			return $result;
		}

		// Get the data
		$rows = $this->get_replace_data( $offset, $per_page );
		if ( $rows instanceof \WP_Error ) {
			return $rows;
		}

		// Convert it to Results
		$results = $this->convert_rows_to_results( $rows['results'], $replacer );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		return [
			'results' => $results,
			'totals' => $totals->to_json(),
			'next' => $rows['next'],
			'rows' => array_reduce( $rows['results'], function( $carry, $item ) {
				return $carry + count( $item['results'] );
			}, 0 ),
		];
	}

	/**
	 * Get the replacement data. We use a different paging method than searching as we're also replacing rows, and this means we can't page.
	 *
	 * @param String $offset Current offset token.
	 * @param Int    $limit Page limit.
	 * @return \WP_Error|Array Data array
	 */
	public function get_replace_data( $offset, $limit ) {
		$results = [];
		$parts = explode( '-', $offset );
		$current_source = $this->sources[0]->get_type();
		$offset = 0;
		$remaining_limit = $limit;
		$last_row_id = false;

		if ( count( $parts ) > 1 ) {
			$current_source = implode( '-', array_slice( $parts, 0, -1 ) );
			$offset = intval( $parts[ count( $parts ) - 1 ], 10 );
		}

		foreach ( $this->sources as $source_pos => $source ) {
			if ( $source->get_type() === $current_source ) {
				$source_results = $source->get_matched_rows_offset( $this->search, $offset, $remaining_limit, $this->flags->is_regex() );
				if ( $source_results instanceof \WP_Error ) {
					return $source_results;
				}

				if ( count( $source_results ) > 0 ) {
					$last_row_id = $current_source . '-';
					$last_row_id .= strval( intval( $source_results[ count( $source_results ) - 1 ][ $source->get_table_id() ], 10 ) );
				}

				// Merge with existing results
				$results[] = [
					'source_pos' => $source_pos,
					'results' => $source_results,
				];

				// Do we have any more to get?
				$remaining_limit -= count( $source_results );
				if ( $remaining_limit <= 0 || $source_pos + 1 >= count( $this->sources ) ) {
					break;
				}

				// Move on to next source and reset offset to 0
				$current_source = $this->sources[ $source_pos + 1 ]->get_type();
				$offset = 0;
			}
		}

		return [
			'results' => $results,
			'next' => $last_row_id && $remaining_limit === 0 ? $last_row_id : false,
		];
	}

	/**
	 * Convert database rows into Result objects
	 *
	 * @internal
	 * @param Array   $source_results Array of row data.
	 * @param Replace $replacer Replace object.
	 * @return Result[]|\WP_Error Array of results
	 */
	public function convert_rows_to_results( array $source_results, Replace $replacer ) {
		$results = [];

		// Loop over the source results, extracting the source and results for that source
		foreach ( $source_results as $result ) {
			$source = $this->sources[ $result['source_pos'] ];
			$rows = $result['results'];

			// Loop over the results for the source
			foreach ( $rows as $row ) {
				$columns = array_keys( $row );
				$match_columns = [];
				$row_id = 0;

				foreach ( array_slice( $columns, 1 ) as $column ) {
					$row_id = intval( array_values( $row )[0], 10 );
					$replacement = $replacer->get_replace_positions( $this->search, $row[ $column ] );
					$contexts = Match::get_all( $this->search, $source->get_flags(), $replacement, $row[ $column ] );

					if ( count( $contexts ) > 0 ) {
						$match_columns[] = new Match_Column( $column, $source->get_column_label( $column, $row[ $column ] ), $replacer->get_global_replace( $this->search, $row[ $column ] ), $contexts );
					}
				}

				if ( count( $match_columns ) > 0 && $row_id > 0 ) {
					$results[] = new Result( $row_id, $source, $match_columns, $row );
				}
			}
		}

		return $results;
	}

	/**
	 * Convert a set of search results into JSON
	 *
	 * @param Array $results Array of Results.
	 * @return Array JSON array
	 */
	public function results_to_json( array $results ) {
		$json = [];

		foreach ( $results as $result ) {
			$json[] = $result->to_json();
		}

		return $json;
	}

	/**
	 * Return the associated Search_Flags
	 *
	 * @return Search_Flags Search_Flags object
	 */
	public function get_flags() {
		return $this->flags;
	}
}
