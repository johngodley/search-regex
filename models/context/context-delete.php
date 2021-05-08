<?php

namespace SearchRegex;

/**
 * Context for a deleted value
 */
class Match_Context_Delete extends Match_Context_Value {
	const TYPE_DELETE = 'delete';

	public function get_type() {
		return self::TYPE_DELETE;
	}

	public function needs_saving() {
		return true;
	}
}
