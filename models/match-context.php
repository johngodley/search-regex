<?php

namespace SearchRegex;

/**
 * A group of matches within the same area of a column
 */
class Match_Context {
	const MATCH_LIMIT = 100;
	const CONTEXT_RANGE = 100;
	const CHARS_BEFORE = 50;
	const CHARS_AFTER = 60;

	/**
	 * Context ID
	 *
	 * @var Int
	 **/
	private $context_id;

	/**
	 * Context
	 *
	 * @var String|null
	 **/
	private $context = null;

	/**
	 * Array of matches
	 *
	 * @var Match[]
	 **/
	private $matches = [];

	/**
	 * Total number of matches
	 *
	 * @var Int
	 **/
	private $match_count = 0;

	/**
	 * Create a Match_Context with a given context ID
	 *
	 * @param int $context_id Context ID.
	 */
	public function __construct( $context_id ) {
		$this->context_id = $context_id;
	}

	/**
	 * Return the number of matches within this context
	 *
	 * @return Int Match count
	 */
	public function get_match_count() {
		return $this->match_count;
	}

	/**
	 * Convert the Match_Context to to_json
	 *
	 * @return Array{context_id: int, context: string|null, matches: array, match_count: int} JSON
	 */
	public function to_json() {
		$matches = [];

		foreach ( $this->matches as $match ) {
			$matches[] = $match->to_json();
		}

		return [
			'context_id' => $this->context_id,
			'context' => $this->context,
			'matches' => $matches,
			'match_count' => $this->match_count,
		];
	}

	/**
	 * Determine if the Match object is within this context.
	 *
	 * @param Match $match The match to check.
	 * @return Bool true if within the context, false otherwise
	 */
	public function is_within_context( Match $match ) {
		if ( $this->context === null ) {
			return true;
		}

		$last_match = $this->matches[ count( $this->matches ) - 1 ];

		return $match->get_position() - $last_match->get_position() < self::CONTEXT_RANGE;
	}

	/**
	 * Add a Match to this context
	 *
	 * @param Match  $match The match to do.
	 * @param String $value The column value.
	 * @return void
	 */
	public function add_match( Match $match, $value ) {
		$this->match_count++;

		if ( count( $this->matches ) === self::MATCH_LIMIT ) {
			return;
		}

		// Add match to list
		$this->matches[] = $match;

		// Expand the context to include everything from the first to last match
		$start_pos = max( 0, $this->matches[0]->get_position() - self::CHARS_BEFORE );

		$end_pos = $match->get_position() + strlen( $match->get_matched_text() ) + self::CHARS_AFTER;

		$this->context = mb_substr( $value, $start_pos, $end_pos - $start_pos, 'UTF-8' );

		// Finally update the match
		$match->set_context( $match->get_position() - $start_pos );
	}

	/**
	 * Find the Match that exists at the given position
	 *
	 * @param int $pos_id Position.
	 * @return Match|Bool Match at position
	 */
	public function get_match_at_position( $pos_id ) {
		foreach ( $this->matches as $match ) {
			if ( $match->get_position() === $pos_id ) {
				return $match;
			}
		}

		return false;
	}
}
