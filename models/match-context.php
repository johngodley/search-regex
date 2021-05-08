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
	 * Create a Match_Context_String with a given context ID
	 *
	 * @param int $context_id Context ID.
	 */
	public function __construct( $context_id = 0 ) {
		$this->context_id = $context_id;
	}

	/**
	 * Set the context ID
	 *
	 * @param integer $context_id New context ID.
	 * @return void
	 */
	public function set_context_id( $context_id ) {
		$this->context_id = $context_id;
	}

	/**
	 * Is the context the same type as this context?
	 *
	 * @param Match_Context $context Context to compare.
	 * @return boolean
	 */
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
	 * @return array JSON
	 */
	public function to_json() {
		return [
			'context_id' => $this->context_id,
			'type' => $this->get_type(),
		];
	}

	/**
	 * Get context type
	 *
	 * @return string
	 */
	abstract public function get_type();
}
