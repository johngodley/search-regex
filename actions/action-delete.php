<?php

namespace SearchRegex;

class Action_Delete extends Action {
	private $deleted = 0;

	public function to_json() {
		return [
			'action' => 'delete',
		];
	}

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		if ( $this->should_save() ) {
			$this->deleted++;

			$this->log_action( 'Delete: ' . $source->get_table_name() . ' row ' . $row_id );

			if ( searchregex_can_save() ) {
				$source->delete_row( $row_id );
			}
		}

		return $columns;
	}

	public function get_totals() {
		if ( $this->deleted > 0 ) {
			return [
				[
					'name' => 'delete',
					'value' => $this->deleted,
				],
			];
		}

		return [];
	}
}
