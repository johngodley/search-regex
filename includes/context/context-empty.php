<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context for an empty value (i.e. null)
 */
class Empty_Type extends Context\Context {
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
