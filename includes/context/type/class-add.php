<?php

namespace SearchRegex\Context\Type;

use SearchRegex\Context;

/**
 * A context has been added.
 */
class Add extends Context\Type\Value {
	const TYPE_ADD = 'add';

	public function get_type() {
		return self::TYPE_ADD;
	}

	public function is_matched() {
		return true;
	}

	public function needs_saving() {
		return true;
	}
}
