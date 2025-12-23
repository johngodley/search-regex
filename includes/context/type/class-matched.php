<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context for a matched value
 */
class Matched extends Context\Type\Value {
	const TYPE_MATCH = 'match';

	public function get_type() {
		return self::TYPE_MATCH;
	}

	public function is_matched() {
		return true;
	}
}
