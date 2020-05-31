<?php

namespace SearchRegex;

/**
 * Collects totals for a search across multiple sources
 */
class Totals {
	/**
	 * Total rows and matches
	 *
	 * @var array<string,array{rows: int, matched_rows: int, matched_phrases: int}>
	 */
	private $totals = [];

	/**
	 * The grand totals across all sources
	 *
	 * @var array{rows: int, matched_rows: int, matched_phrases: int}
	 */
	private $grand_totals = [
		'rows' => 0,
		'matched_rows' => 0,
		'matched_phrases' => 0,
	];

	/**
	 * Get total number of matches and rows across each source
	 *
	 * @param Array  $sources Array of search sources.
	 * @param String $search Search phrase.
	 * @return \WP_Error|Bool Array of totals
	 */
	public function get_totals( array $sources, $search ) {
		foreach ( $sources as $source ) {
			$rows = $source->get_total_rows();
			if ( $rows instanceof \WP_Error ) {
				return $rows;
			}

			$name = $source->get_type();

			// Total for each source
			$this->totals[ $name ] = [
				'rows' => intval( $rows, 10 ),
				'matched_rows' => 0,
				'matched_phrases' => 0,
			];

			if ( ! $source->get_flags()->is_regex() ) {
				$matches = $source->get_total_matches( $search );
				if ( $matches instanceof \WP_Error ) {
					return $matches;
				}

				$this->totals[ $name ]['matched_rows'] += intval( $matches['rows'], 10 );
				$this->totals[ $name ]['matched_phrases'] += intval( $matches['matches'], 10 );
			}

			// Add to grand totals
			$this->add_to_grand_total( $this->totals[ $name ] );
		}

		return true;
	}

	/**
	 * Add a source total to the grand total
	 *
	 * @param array{rows: int, matched_rows: int, matched_phrases: int} $total The source totals.
	 * @return void
	 */
	private function add_to_grand_total( $total ) {
		$this->grand_totals['rows'] += $total['rows'];
		$this->grand_totals['matched_rows'] += $total['matched_rows'];
		$this->grand_totals['matched_phrases'] += $total['matched_phrases'];
	}

	/**
	 * Get totals for a source
	 *
	 * @param String $source_name Source name.
	 * @param Bool   $regex Is this a regex search.
	 * @return Int Number of matches for the row
	 */
	public function get_total_for_source( $source_name, $regex ) {
		if ( isset( $this->totals[ $source_name ] ) ) {
			return $regex ? $this->totals[ $source_name ]['rows'] : $this->totals[ $source_name ]['matched_rows'];
		}

		return 0;
	}

	/**
	 * Get the next page offset.
	 *
	 * @param Int  $next_offset The offset of the next page.
	 * @param Bool $is_regex Is this a regex search.
	 * @return Int|false Next offset, or false if no next offset
	 */
	public function get_next_page( $next_offset, $is_regex ) {
		if ( $is_regex ) {
			return $next_offset >= $this->grand_totals['rows'] ? false : $next_offset;
		}

		return $next_offset >= $this->grand_totals['matched_rows'] ? false : $next_offset;
	}

	/**
	 * Return the grand totals as JSON
	 *
	 * @return array{rows: int, matched_rows: int, matched_phrases: int}
	 */
	public function to_json() {
		return $this->grand_totals;
	}
}
