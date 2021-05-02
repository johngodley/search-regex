<?php

namespace SearchRegex;

class Match_Context_String extends Match_Context {
	const TYPE_STRING = 'string';
	const MATCH_LIMIT = 100;
	const CONTEXT_RANGE = 100;
	const CHARS_BEFORE = 50;
	const CHARS_AFTER = 60;

	/**
	 * Context
	 *
	 * @var String|null
	 **/
	private $context = null;

	private $context_crop = [];

	/**
	 * Array of matches
	 *
	 * @var Match[]
	 **/
	private $matches = [];

	private $search;
	private $flags;
	private $value_type = '';

	public function __construct( $search, Search_Flags $flags ) {
		$this->set_search( $search, $flags );
	}

	public function set_search( $search, Search_Flags $flags ) {
		$this->search = $search;
		$this->flags = $flags;
	}

	public function set_type( $type ) {
		$this->value_type = $type;
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
	 * Convert the Match_Context_String to to_json
	 *
	 * @return Array{context_id: int, context: string|null, matches: array, match_count: int} JSON
	 */
	public function to_json() {
		$matches = [];

		foreach ( $this->matches as $match ) {
			$matches[] = $match->to_json();
		}

		return array_merge(
			parent::to_json(),
			[
				'context' => $this->context,
				'crop' => $this->context_crop,
				'search' => $this->search,
				'flags' => $this->flags->to_json(),
				'matches' => $matches,
				'match_count' => $this->match_count,
				'value_type' => $this->value_type,
			]
		);
	}

	/**
	 * Determine if the Match object is within this context.
	 *
	 * @param Matched_Item $match The match to check.
	 * @return Bool true if within the context, false otherwise
	 */
	public function is_within_context( Matched_Item $match ) {
		if ( count( $this->matches ) === 0 ) {
			return true;
		}

		$last_match = $this->matches[ count( $this->matches ) - 1 ];

		return $match->get_position() - $last_match->get_position() < self::CONTEXT_RANGE;
	}

	/**
	 * Add a Match to this context
	 *
	 * @param Matched_Item $match The match to do.
	 * @param String       $value The column value.
	 * @return void
	 */
	public function add_match( Matched_Item $match, $value ) {
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
		$this->context_crop = [];

		if ( $start_pos > 0 ) {
			$this->context_crop['start'] = $start_pos;
		}

		if ( $end_pos < strlen( $value ) ) {
			$this->context_crop['end'] = $end_pos;
		}

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

	public function get_type() {
		return self::TYPE_STRING;
	}

	public function get_value() {
		return $this->context;
	}

	public function is_equal( Match_Context $context ) {
		if ( parent::is_equal( $context ) ) {
			return $this->context === $context->context;
		}

		return false;
	}
}
