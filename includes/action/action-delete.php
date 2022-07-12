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
	 *
	 * @var integer
	 */
	private $deleted = 0;

	public function to_json() {
		return [
			'action' => 'delete',
		];
	}

	public function perform( $row_id, array $row, Source\Source $source, array $columns ) {
		if ( $this->should_save() ) {
			$this->deleted++;

			$this->log_action( 'Delete: ' . $source->get_table_name() . ' row ' . (string) $row_id );

			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				$source->delete_row( $row_id );
			}
		}

		return $columns;
	}
}
