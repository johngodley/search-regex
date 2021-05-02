<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Value;

class Action_Run_Action extends Action {
	private $hook;
	private $schema;

	public function __construct( array $options, Schema $schema ) {
		if ( isset( $options['hook'] ) ) {
			$this->hook = preg_replace( '/[A-Za-z0-9_-]/', '', $options['hook'] );

			if ( ! has_action( $this->hook ) ) {
				$this->hook = false;
			}
		}

		$this->schema = $schema->get_sources()[0];
	}

	public function to_json() {
		return [
			'action' => 'action',
			'actionOption' => [
				'hook' => $this->hook,
			],
		];
	}

	// We want this action to return data
	public function should_save() {
		return false;
	}

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		if ( ! $this->hook || ! $this->should_save() ) {
			return $columns;
		}

		foreach ( $columns as $pos => $column ) {
			foreach ( $this->columns as $action_column ) {
				if ( $source->is_type( $action_column->get_source_name() ) && $action_column->is_for_column( $column->get_column_id() ) ) {
					do_action( $this->hook, $action_column, $row );
					break;
				}
			}
		}

		return $columns;
	}
}
