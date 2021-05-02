<?php

namespace SearchRegex;

class Action_Nothing extends Action {
	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		return $columns;
	}

	public function to_json() {
		return [
			'action' => 'nothing',
		];
	}
}
