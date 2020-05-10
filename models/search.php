<?php

namespace SearchRegex;

use SearchRegex\Result_Collection;
use SearchRegex\Match;

require_once __DIR__ . '/source.php';
require_once __DIR__ . '/source-manager.php';
require_once __DIR__ . '/match.php';
require_once __DIR__ . '/match-context.php';
require_once __DIR__ . '/match-column.php';
require_once __DIR__ . '/search-flags.php';
require_once __DIR__ . '/source-flags.php';

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
	 * @var Search_Source[]
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
	 * Get total number of matches and rows across each source
	 *
	 * @internal
	 * @param Int $offset Page offset.
	 * @return \WP_Error|Array<String,Array> Array of totals
	 */
	private function get_totals( $offset ) {
		$source_totals = [];

		foreach ( $this->sources as $source ) {
			$rows = $source->get_total_rows();
			if ( is_wp_error( $rows ) && is_object( $rows ) ) {
				return $rows;
			}

			// Total for each source
			$source_totals[ $source->get_type( [] ) ] = [
				'rows' => intval( $rows, 10 ),
			];
		}

		// First request also returns the total matched
		if ( $offset === 0 ) {
			foreach ( $this->sources as $source ) {
				$name = $source->get_type( [] );

				$source_totals[ $name ]['matched_rows'] = 0;
				$source_totals[ $name ]['matched_phrases'] = 0;

				if ( ! $this->flags->is_regex() ) {
					$matches = $source->get_total_matches( $this->search );
					if ( $matches instanceof \WP_Error ) {
						return $matches;
					}

					$source_totals[ $name ]['matched_rows'] += $matches['rows'];
					$source_totals[ $name ]['matched_phrases'] += $matches['matches'];
				}
			}
		}

		return $source_totals;
	}

	/**
	 * Return number of matches for a source. Uses existing `$totals`, or will use the database.
	 *
	 * @param Search_Source $source Source to get matches for.
	 * @param Array         $totals Existing totals array.
	 * @return Int|\WP_Error Number of rows
	 */
	private function get_matched_rows( Search_Source $source, array $totals ) {
		if ( $this->flags->is_regex() ) {
			return $totals['rows'];
		}

		if ( isset( $totals['matched_rows'] ) ) {
			return $totals['matched_rows'];
		}

		$matches = $source->get_total_matches( $this->search );
		if ( $matches instanceof \WP_Error ) {
			return $matches;
		}

		return $matches['rows'];
	}

	/**
	 * Get the match data for a search. We merge all result sets for all sources together, and then paginate across them.
	 *
	 * @internal
	 * @param Int   $absolute_offset Absolute page offset across all sources (assuming they don't change).
	 * @param Int   $limit Page limit.
	 * @param Array $totals Source totals.
	 * @return \WP_Error|Array Data array
	 */
	private function get_data( $absolute_offset, $limit, array $totals ) {
		$results = [];
		$current_offset = 0;
		$source_offset = 0;
		$remaining_limit = $limit;

		// Go through each row and see if our $absolute_offset + $limit is within it's result set
		foreach ( $this->sources as $source_pos => $source ) {
			$source_totals = $totals[ $source->get_type( [] ) ];
			$num_rows = $this->get_matched_rows( $source, $source_totals );

			if ( $num_rows instanceof \WP_Error ) {
				return $num_rows;
			}

			// Are we within the correct result set?
			$current = $absolute_offset + ( $limit - $remaining_limit );
			if ( $current >= $current_offset && $current < $current_offset + $num_rows ) {
				// Adjust for the current source offset
				$source_offset = $current - $current_offset;
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
		return $this->rows_to_results( $results, $replacer );
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
	public function get_results( Replace $replacer, $offset, $per_page, $limit = 0 ) {
		$totals = $this->get_totals( $offset );
		if ( $totals instanceof \WP_Error ) {
			return $totals;
		}

		$rows = $this->get_data( $offset, $per_page, $totals );
		if ( $rows instanceof \WP_Error ) {
			return $rows;
		}

		$results = $this->rows_to_results( (array) $rows, $replacer );

		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		$grand = $this->get_as_grand_total( $totals );
		$previous = max( 0, $offset - $per_page );

		// We always go in $per_page groups, but we need to limit if we only need a few more to fill a result set
		if ( $limit > 0 && $limit <= count( $results ) ) {
			$next = min( $offset + $limit, $grand['rows'] );
		} else {
			$next = min( $offset + $per_page, $grand['rows'] );   // TODO this isn't going to end in simple search
		}

		$results = array_slice( $results, 0, $limit === 0 ? $per_page : $limit );

		if ( $next === $offset ) {
			$next = false;
		}

		if ( $previous === $offset ) {
			$previous = false;
		}

		return [
			'results' => $results,
			'totals' => $grand,
			'progress' => [
				'current' => $offset,
				'rows' => count( $results ),
				'previous' => $previous,
				'next' => $next,
			],
		];
	}

	/**
	 * Get source totals as a grand total.
	 *
	 * @param Array $source_totals Source totals.
	 * @return Array Grand totals
	 */
	private function get_as_grand_total( array $source_totals ) {
		$totals = [ 'rows' => 0 ];

		foreach ( $source_totals as $source ) {
			$totals['rows'] += $source['rows'];

			if ( isset( $source['matched_rows'] ) ) {
				if ( ! isset( $totals['matched_rows'] ) ) {
					$totals['matched_rows'] = 0;
				}

				if ( ! isset( $totals['matched_phrases'] ) ) {
					$totals['matched_phrases'] = 0;
				}

				$totals['matched_rows'] += $source['matched_rows'];
				$totals['matched_phrases'] += $source['matched_phrases'];
			}
		}

		return $totals;
	}

	/**
	 * Convert database rows into Result objects
	 *
	 * @internal
	 * @param Array   $source_results Array of row data.
	 * @param Replace $replacer Replace object.
	 * @return Result[]|\WP_Error Array of results
	 */
	private function rows_to_results( array $source_results, Replace $replacer ) {
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
					$contexts = Match::get_all( $this->search, $this->flags, $replacement, $row[ $column ] );

					if ( count( $contexts ) > 0 ) {
						$match_columns[] = new Match_Column( $column, $source->get_column_label( $column ), $replacer->get_global_replace( $this->search, $row[ $column ] ), $contexts );
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
}
