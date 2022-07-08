<?php

namespace SearchRegex\Context;

require_once __DIR__ . '/class-value-type.php';
require_once __DIR__ . '/context-value.php';
require_once __DIR__ . '/context-matched.php';
require_once __DIR__ . '/context-add.php';
require_once __DIR__ . '/context-delete.php';
require_once __DIR__ . '/context-pair.php';
require_once __DIR__ . '/context-empty.php';
require_once __DIR__ . '/context-replace.php';
require_once __DIR__ . '/context-text.php';

/**
 * A group of matches within the same area of a column
 */
abstract class Context {
	/**
	 * Context ID
	 *
	 * @var Int
	 **/
	protected $context_id = 0;

	/**
	 * Create a Context_String with a given context ID
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
	 * @param Context $context Context to compare.
	 * @return boolean
	 */
	public function is_equal( Context $context ) {
		return $this->get_type() === $context->get_type();
	}

	/**
	 * Has this been matched?
	 *
	 * @return boolean
	 */
	public function is_matched() {
		return false;
	}

	/**
	 * Get number of matches
	 *
	 * @return integer
	 */
	public function get_match_count() {
		return 1;
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
	 * Convert the Context_String to to_json
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
