<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Action\Action;
use SearchRegex\Source;
use SearchRegex\Plugin;

/**
 * Action to delete rows
 */
class Delete extends Action {
	/**
	 * Track how many rows have been deleted
	 */
	private int $deleted = 0;

	/**
	 * @return array<string, mixed>
	 */
	public function to_json() {
		return [
			'action' => 'delete',
		];
	}

	/**
	 * @param int $row_id
	 * @param array<string, mixed> $row
	 * @param Source\Source $source
	 * @param array<\SearchRegex\Search\Column> $columns
	 * @return array<\SearchRegex\Search\Column>
	 */
	public function perform( $row_id, array $row, Source\Source $source, array $columns ) {
		if ( $this->should_save() ) {
			$this->deleted++;

			$this->log_action( 'Delete: ' . $source->get_table_name() . ' row ' . (string) $row_id );

			if ( Plugin\Settings::init()->can_save() ) {
				$source->delete_row( $row_id );
			}
		}

		return $columns;
	}
}
