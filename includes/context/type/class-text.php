<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;
use SearchRegex\Search;

/**
 * Context for a substring(s) in a string
 *
 * @phpstan-type TextCrop array{start?: int, end?: int}
 * @phpstan-type TextContextJson array{
 *   context_id: int,
 *   type: string,
 *   context: mixed,
 *   crop: TextCrop,
 *   search: string,
 *   flags: array<string, mixed>,
 *   matches: array<int, array<string, mixed>>,
 *   match_count: int,
 *   value_type: string
 * }
 */
class Text extends Context\Context {
	const TYPE_STRING = 'string';
	const MATCH_LIMIT = 100;
	const CONTEXT_RANGE = 100;
	const CHARS_BEFORE = 50;
	const CHARS_AFTER = 60;

	/**
	 * Context
	 */
	private ?string $context = null;

	/**
	 * Crop values
	 *
	 * @var TextCrop
	 */
	private array $context_crop = [];

	/**
	 * Array of matches
	 *
	 * @var Search\Text[]
	 */
	private array $matches = [];

	/**
	 * Text being searched
	 */
	private string $search;

	/**
	 * Search flags
	 */
	private Search\Flags $flags;

	/**
	 * Value type
	 */
	private string $value_type = '';

	/**
	 * Number of matches
	 */
	private int $match_count = 0;

	/**
	 * Constructor
	 *
	 * @param string       $search Search.
	 * @param Search\Flags $flags Flags.
	 * @phpstan-ignore constructor.missingParentCall
	 */
	public function __construct( $search, Search\Flags $flags ) {
		$this->search = '';
		$this->flags = new Search\Flags();
		$this->set_search( $search, $flags );
	}

	/**
	 * Set the search
	 *
	 * @param string       $search Search.
	 * @param Search\Flags $flags Flags.
	 * @return void
	 */
	public function set_search( $search, Search\Flags $flags ) {
		$this->search = $search;
		$this->flags = $flags;
	}

	/**
	 * Set the value type
	 *
	 * @param string $type Type.
	 * @return void
	 */
	public function set_type( $type ) {
		$this->value_type = $type;
	}

	/**
	 * Return the number of matches within this context
	 *
	 * @return int Match count
	 */
	public function get_match_count() {
		return $this->match_count;
	}

	/**
	 * @return bool
	 */
	public function is_matched() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function needs_saving() {
		return true;
	}

	/**
	 * Convert the Context\Type\Text to to_json
	 *
	 * @return TextContextJson
	 */
	public function to_json() {
		$matches = [];

		foreach ( $this->matches as $match ) {
			$matches[] = $match->to_json();
		}

		$parent_json = parent::to_json();

		return [
			'context_id' => $parent_json['context_id'],
			'type' => $parent_json['type'],
			'context' => $this->context,
			'crop' => $this->context_crop,
			'search' => $this->search,
			'flags' => $this->flags->to_json(),
			'matches' => $matches,
			'match_count' => $this->match_count,
			'value_type' => $this->value_type,
		];
	}

	/**
	 * Determine if the Match object is within this context.
	 *
	 * @param Search\Text $match The match to check.
	 * @return bool true if within the context, false otherwise
	 */
	public function is_within_context( Search\Text $match ) {
		if ( count( $this->matches ) === 0 ) {
			return true;
		}

		$last_match = $this->matches[ count( $this->matches ) - 1 ];

		return $match->get_position() - $last_match->get_position() < self::CONTEXT_RANGE;
	}

	/**
	 * Add a Match to this context
	 *
	 * @param Search\Text $match The match to do.
	 * @param string      $value The column value.
	 * @return void
	 */
	public function add_match( Search\Text $match, $value ) {
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
	 * @return Search\Text|false Match at position
	 */
	public function get_match_at_position( $pos_id ) {
		foreach ( $this->matches as $match ) {
			if ( $match->get_position() === $pos_id ) {
				return $match;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return self::TYPE_STRING;
	}

	/**
	 * Get value
	 *
	 * @return string|null
	 */
	public function get_value() {
		return $this->context;
	}

	/**
	 * @param Context\Context $context Context to compare.
	 * @return bool
	 */
	public function is_equal( Context\Context $context ) {
		if ( parent::is_equal( $context ) && $context instanceof Context\Type\Text ) {
			return $this->context === $context->context;
		}

		return false;
	}

	/**
	 * Get matches from this context
	 *
	 * @return Search\Text[]
	 */
	public function get_matches() {
		return $this->matches;
	}
}
