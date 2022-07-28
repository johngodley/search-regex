<?php

namespace SearchRegex\Search;

use SearchRegex\Context;

/**
 * Represents a single match
 */
class Text {
	/**
	 * Position ID
	 *
	 * @readonly
	 * @var Int
	 **/
	private $pos_id;

	/**
	 * Matched string
	 *
	 * @readonly
	 * @var String
	 **/
	private $match;

	/**
	 * Context offset
	 *
	 * @var Int
	 **/
	private $context_offset = 0;

	/**
	 * Replacement
	 *
	 * @var String|null
	 **/
	private $replacement = null;

	/**
	 * Array of captured data
	 *
	 * @var String[]
	 **/
	private $captures;

	/**
	 * Create a Match given the matched phrase, the match offset, and what the match is replaced with
	 *
	 * @param String $match The matched phrased. Typical the search phrase unless a regular expression search.
	 * @param int    $match_offset The offset within the column.
	 * @param String $replacement The replaced value, if one is supplied.
	 */
	public function __construct( $match, $match_offset = 0, $replacement = null ) {
		$this->pos_id = intval( $match_offset, 10 );
		$this->match = "$match";
		$this->replacement = $replacement;
		$this->captures = [];
	}

	/**
	 * Set the replacement text for this match
	 *
	 * @param string $replacement Replacement text.
	 * @return void
	 */
	public function set_replacement( $replacement ) {
		$this->replacement = $replacement;
	}

	/**
	 * Add a regular expression capture value
	 *
	 * @param String $capture Captured value.
	 * @return void
	 */
	public function add_capture( $capture ) {
		$this->captures[] = $capture;
	}

	/**
	 * Get match position
	 *
	 * @return Int Position
	 */
	public function get_position() {
		return $this->pos_id;
	}

	/**
	 * Get matched text
	 *
	 * @return String Matched text
	 */
	public function get_matched_text() {
		return $this->match;
	}

	/**
	 * Set the context offset - the offset within the context that this match belongs to
	 *
	 * @param Int $context_offset The context offset.
	 * @return void
	 */
	public function set_context( $context_offset ) {
		$this->context_offset = $context_offset;
	}

	/**
	 * Convert this match to JSON
	 *
	 * @return Array JSON
	 */
	public function to_json() {
		return [
			'pos_id' => $this->pos_id,
			'context_offset' => $this->context_offset,
			'match' => $this->match,
			'replacement' => $this->replacement,
			'captures' => $this->captures,
		];
	}

	/**
	 * Encode a search as a regular expression
	 *
	 * @param String $search Search phrase.
	 * @param Flags  $flags Is this regular expression.
	 * @return String Encoded search phrase
	 */
	public static function get_pattern( $search, Flags $flags ) {
		$pattern = \preg_quote( $search, '@' );

		if ( $flags->is_regex() ) {
			$pattern = str_replace( '@', '\\@', $search );
		}

		$pattern = '@' . $pattern . '@';

		if ( $flags->is_case_insensitive() ) {
			$pattern .= 'i';
		}

		// UTF-8 support
		return $pattern . 'u';
	}

	/**
	 * Get all matches for a search phrase on a column
	 *
	 * @param String $search The search phrase.
	 * @param Flags  $flags Any search flags.
	 * @param Array  $replacements A matching set of replacements.
	 * @param String $column_value The content to match.
	 * @return Array Array of Match contexts
	 */
	public static function get_all( $search, Flags $flags, array $replacements, $column_value ) {
		$pattern = self::get_pattern( $search, $flags );
		$contexts = [];

		if ( \preg_match_all( $pattern, $column_value, $searches, PREG_OFFSET_CAPTURE ) > 0 ) {
			$current_context = new Context\Type\Text( $search, $flags );
			$current_context->set_type( Context\Value_Type::get( $column_value ) );
			$contexts[] = $current_context;

			// Go through each search match and create a Match
			foreach ( $searches[0] as $match_pos => $match ) {
				// Adjust for UTF8 strings
				$pos = mb_strlen( substr( $column_value, 0, $match[1] ), 'utf-8' );

				// Create a match
				$match = new self( $match[0], $pos, isset( $replacements[ $match_pos ] ) ? $replacements[ $match_pos ] : null );

				// Add any captures
				foreach ( array_slice( $searches, 1 ) as $capture ) {
					$match->add_capture( $capture[ $match_pos ][0] );
				}

				// Is the match within range of the current context
				if ( ! $current_context->is_within_context( $match ) ) {
					// No - create a new context
					$current_context = new Context\Type\Text( $search, $flags );
					$current_context->set_type( Context\Value_Type::get( $column_value ) );
					$current_context->set_context_id( count( $contexts ) );
					$contexts[] = $current_context;
				}

				// Add the match to the context
				$current_context->add_match( $match, $column_value );
			}
		}

		return $contexts;
	}

	/**
	 * Return the replacement at the matches position of the given text.
	 *
	 * @param String $text Text value.
	 * @return String The $text value, with the replacement inserted at the Match position
	 */
	public function replace_at_position( $text ) {
		if ( $this->replacement !== null ) {
			return mb_substr( $text, 0, $this->pos_id, 'UTF-8' ) . $this->replacement . mb_substr( $text, $this->pos_id + mb_strlen( $this->match, 'UTF-8' ), null, 'UTF-8' );
		}

		return $text;
	}
}
