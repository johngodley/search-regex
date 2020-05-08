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
	/** @var String */
	private $search;
	/** @var Search_Source[] */
	private $sources = [];
	/** @var Search_Flags */
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
	 * @return \WP_Error|Array<String,Int> Array of totals
	 */
	private function get_totals( $offset ) {
		$totals = [ 'rows' => 0 ];

		foreach ( $this->sources as $source ) {
			$rows = $source->get_total_rows();
			if ( is_wp_error( $rows ) && is_object( $rows ) ) {
				return $rows;
			}

			$totals['rows'] += intval( $rows, 10 );
		}

		// First request also returns the total matched
		if ( $offset === 0 ) {
			$totals['matched_rows'] = 0;
			$totals['matched_phrases'] = 0;

			foreach ( $this->sources as $source ) {
				if ( ! $this->flags->is_regex() ) {
					$matches = $source->get_total_matches( $this->search );
					if ( $matches instanceof \WP_Error ) {
						return $matches;
					}

					$totals['matched_rows'] += $matches['rows'];
					$totals['matched_phrases'] += $matches['matches'];
				}
			}
		}

		return $totals;
	}

	/**
	 * Get the match data for a search
	 *
	 * @internal
	 * @param Int $offset Page offset.
	 * @param Int $limit Page limit.
	 * @return \WP_Error|Array Data array
	 */
	private function get_data( $offset, $limit ) {
		if ( $this->flags->is_regex() ) {
			return $this->sources[0]->get_all_rows( $this->search, $offset, $limit );
		}

		return $this->sources[0]->get_matched_rows( $this->search, $offset, $limit );
	}

	/**
	 * Get a single database row
	 *
	 * @param int     $row_id Row ID to return.
	 * @param Replace $replacer The Replace object used when replacing data.
	 * @return \WP_Error|Array Return a single database row, or WP_Error on error
	 */
	public function get_row( $row_id, Replace $replacer ) {
		global $wpdb;

		$row = $this->sources[0]->get_row( $row_id );

		// Error
		if ( is_wp_error( $row ) ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error );
		}

		return $this->rows_to_results( [ $row ], $replacer );
	}

	/**
	 * Perform the search, returning a result array that contains the totals, the progress, and an array of Result objects
	 *
	 * @param Replace $replacer The replacer which performs any replacements.
	 * @param int     $offset Current page offset.
	 * @param int     $limit Per page limit.
	 * @return Array|\WP_Error Array containing `totals`, `progress`, and `results`
	 */
	public function get_results( Replace $replacer, $offset, $limit = 5 ) {
		// TODO return totals of each source
		$totals = $this->get_totals( $offset );
		$rows = $this->get_data( $offset, $limit );

		if ( $totals instanceof \WP_Error ) {
			return $totals;
		}

		if ( $rows instanceof \WP_Error ) {
			return $rows;
		}

		$results = $this->rows_to_results( (array) $rows, $replacer );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		$previous = max( 0, $offset - $limit );
		$next = min( $offset + $limit, $totals['rows'] );   // TODO this isn't going to end in simple search

		if ( $next === $offset ) {
			$next = false;
		}

		if ( $previous === $offset ) {
			$previous = false;
		}

		return [
			'results' => $results,
			'totals' => $totals,
			'progress' => [
				'current' => $offset,
				'rows' => count( $results ),
				'previous' => $previous,
				'next' => $next,
			],
		];
	}

	/**
	 * Convert database rows into Result objects
	 *
	 * @internal
	 * @param Array   $rows Array of row data.
	 * @param Replace $replacer Replace object.
	 * @return Result[]|\WP_Error Array of results
	 */
	private function rows_to_results( $rows, Replace $replacer ) {
		$results = [];
		$source = $this->sources[0];

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
