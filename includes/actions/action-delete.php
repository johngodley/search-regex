<?php

namespace SearchRegex;

/**
 * Action to delete rows
 */
class Action_Delete extends Action {
	/**
	 * Track how many rows have been deleted
	 *
	 * @var integer
	 */
	private $deleted = 0;

	public function to_json() {
		return [
			'action' => 'delete',
		];
	}

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		if ( $this->should_save() ) {
			$this->deleted++;

			$this->log_action( 'Delete: ' . $source->get_table_name() . ' row ' . (string) $row_id );

			/** @psalm-suppress UndefinedFunction */
			if ( searchregex_can_save() ) {
				$source->delete_row( $row_id );
			}
		}

		return $columns;
	}
}
