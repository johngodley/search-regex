<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Action\Action;

/**
 * Action to do nothing.
 */
class Nothing extends Action {
	// @phpstan-ignore constructor.missingParentCall
	public function __construct() {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_json() {
		return [
			'action' => 'nothing',
		];
	}
}
