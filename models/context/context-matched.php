<?php

namespace SearchRegex;

class Match_Context_Matched extends Match_Context_Value {
	const TYPE_MATCH = 'match';

	public function get_type() {
		return self::TYPE_MATCH;
	}

	public function is_matched() {
		return true;
	}
}
