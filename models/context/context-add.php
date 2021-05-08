<?php

namespace SearchRegex;

/**
 * A context has been added.
 */
class Match_Context_Add extends Match_Context_Value {
	const TYPE_ADD = 'add';

	public function get_type() {
		return self::TYPE_ADD;
	}

	public function needs_saving() {
		return true;
	}
}
