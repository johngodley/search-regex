<?php

namespace SearchRegex;

/**
 * Context for an empty value (i.e. null)
 */
class Match_Context_Empty extends Match_Context {
	const TYPE_EMPTY = 'empty';

	public function get_type() {
		return self::TYPE_EMPTY;
	}

	/**
	 * Get the value
	 *
	 * @return string
	 */
	public function get_value() {
		return '';
	}
}
