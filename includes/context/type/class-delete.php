<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * Context for a deleted value
 */
class Delete extends Context\Type\Value {
	const TYPE_DELETE = 'delete';

	public function get_type() {
		return self::TYPE_DELETE;
	}

	public function is_matched() {
		return true;
	}

	public function needs_saving() {
		return true;
	}
}
