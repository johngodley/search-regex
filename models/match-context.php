<?php

namespace SearchRegex;

/**
 * A group of matches within the same area of a column
 */
abstract class Match_Context {
	/**
	 * Context ID
	 *
	 * @var Int
	 **/
	protected $context_id = 0;

	/**
	 * Total number of matches
	 *
	 * @var Int
	 **/
	protected $match_count = 0;

	/**
	 * Create a Match_Context_String with a given context ID
	 *
	 * @param int $context_id Context ID.
	 */
	public function __construct( $context_id = 0 ) {
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

	public function set_context_id( $context_id ) {
		$this->context_id = $context_id;
	}

	public function is_equal( Match_Context $context ) {
		return $this->get_type() === $context->get_type();
	}

	/**
	 * Has this been matched?
	 *
	 * @return boolean
	 */
	public function is_matched() {
		return true;
	}

	/**
	 * Does this need saving?
	 *
	 * @return boolean
	 */
	public function needs_saving() {
		return false;
	}

	/**
	 * Convert the Match_Context_String to to_json
	 *
	 * @return Array{context_id: int, context: string|null, matches: array, match_count: int} JSON
	 */
	public function to_json() {
		return [
			'context_id' => $this->context_id,
			'type' => $this->get_type(),
		];
	}

	abstract public function get_type();
}

require_once __DIR__ . '/value-type.php';
require_once __DIR__ . '/context/context-value.php';
require_once __DIR__ . '/context/context-matched.php';
require_once __DIR__ . '/context/context-add.php';
require_once __DIR__ . '/context/context-delete.php';
require_once __DIR__ . '/context/context-pair.php';
require_once __DIR__ . '/context/context-empty.php';
require_once __DIR__ . '/context/context-replace.php';
require_once __DIR__ . '/context/context-string.php';
