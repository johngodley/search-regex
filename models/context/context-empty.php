<?php

namespace SearchRegex;

class Match_Context_Empty extends Match_Context {
	const TYPE_EMPTY = 'empty';

	public function get_type() {
		return self::TYPE_EMPTY;
	}

	public function get_value() {
		return '';
	}
}
