<?php

namespace SearchRegex\Search;

use SearchRegex\Source;

/**
 * Collects totals for a search across multiple sources
 */
class Totals {
	/**
	 * Total rows and matches
	 *
	 * @var array<string,array{rows: int, matched_rows: int}>
	 */
	private $totals = [];

	/**
	 * `true` if we have a regex search anywhere, `false` othewise
	 *
	 * @var boolean
	 */
	private $has_advanced = false;

	/**
	 * The grand totals across all sources
	 *
	 * @var array{rows: int, matched_rows: int}
	 */
	private $grand_totals = [
		'rows' => 0,
		'matched_rows' => 0,
	];

	/**
	 * Look through an array of Source\Source objects and see if anything is an advanced search
	 *
	 * @param array<Source\Source> $sources Sources.
	 * @return void
	 */
	private function set_advanced( array $sources ) {
		$advanced = array_filter( $sources, function( $source ) {
			return $source->has_advanced_filter();
		} );

		$this->has_advanced = count( $advanced ) > 0;
	}

	/**
	 * Get total number of matches and rows across each source
	 *
	 * @param array<Source\Source> $sources Sources.
	 * @return \WP_Error|Bool Array of totals
	 */
	public function get_totals( array $sources ) {
		$this->set_advanced( $sources );

		// Loop over each source
		foreach ( $sources as $source ) {
			// Get number of matching rows
			$rows = $source->get_total_rows();
			if ( $rows instanceof \WP_Error ) {
				return $rows;
			}

			$name = $source->get_type();

			// Basic total for each source
			$this->totals[ $name ] = [
				'rows' => intval( $rows, 10 ),
				'matched_rows' => 0,
			];

			// If we have no advanced searches then we get the matched phrase totals
			if ( ! $this->has_advanced ) {
				$matches = $source->get_global_match_total( $source->get_filters() );
				if ( $matches instanceof \WP_Error ) {
					return $matches;
				}

				$this->totals[ $name ]['matched_rows'] += intval( $matches['rows'], 10 );
			}

			// Add to grand totals
			$this->add_to_grand_total( $this->totals[ $name ] );
		}

		return true;
	}

	/**
	 * Add a source total to the grand total
	 *
	 * @param array{rows: int, matched_rows: int} $total The source totals.
	 * @return void
	 */
	private function add_to_grand_total( $total ) {
		$this->grand_totals['rows'] += $total['rows'];
		$this->grand_totals['matched_rows'] += $total['matched_rows'];
	}

	/**
	 * Get total rows for a source
	 *
	 * @param String $source_name Source name.
	 * @return integer Number of matches for the row
	 */
	public function get_total_rows_for_source( $source_name ) {
		if ( isset( $this->totals[ $source_name ] ) ) {
			return $this->totals[ $source_name ]['rows'];
		}

		return 0;
	}

	/**
	 * Get total matched rows for a source
	 *
	 * @param String $source_name Source name.
	 * @return Int Number of matches for the row
	 */
	public function get_matched_rows_for_source( $source_name ) {
		if ( $this->has_advanced ) {
			return $this->get_total_rows_for_source( $source_name );
		}

		if ( isset( $this->totals[ $source_name ] ) ) {
			return $this->totals[ $source_name ]['matched_rows'];
		}

		return 0;
	}

	/**
	 * Get the next page offset.
	 *
	 * @param Int $next_offset The offset of the next page.
	 * @return Int|false Next offset, or false if no next offset
	 */
	public function get_next_page( $next_offset ) {
		if ( $this->has_advanced ) {
			return $next_offset >= $this->grand_totals['rows'] ? false : $next_offset;
		}

		return $next_offset >= $this->grand_totals['matched_rows'] ? false : $next_offset;
	}

	/**
	 * Return the grand totals as JSON
	 *
	 * @return array{rows: int, matched_rows: int}
	 */
	public function to_json() {
		return $this->grand_totals;
	}
}
