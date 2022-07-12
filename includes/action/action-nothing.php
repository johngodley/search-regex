<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Action\Action;

/**
 * Action to do nothing.
 */
class Nothing extends Action {
	public function __construct() {
	}

	public function to_json() {
		return [
			'action' => 'nothing',
		];
	}
}
