<?php

namespace SearchRegex;

/**
 * Action to do nothing.
 */
class Action_Nothing extends Action {
	public function to_json() {
		return [
			'action' => 'nothing',
		];
	}
}
