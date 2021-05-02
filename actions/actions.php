<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Value;

abstract class Action {
	protected $save = false;

	public static function create( $action_type, $action_options, Schema $schema ) {
		if ( $action_type === 'modify' && is_array( $action_options ) ) {
			return new Action_Modify( $action_options, $schema );
		} elseif ( $action_type === 'replace' ) {
			return new Action_Global_Replace( $action_options, $schema );
		} elseif ( $action_type === 'delete' ) {
			return new Action_Delete( $action_options, $schema );
		} elseif ( $action_type === 'export' ) {
			return new Action_Export( $action_options, $schema );
		} elseif ( $action_type === 'action' ) {
			return new Action_Run_Action( $action_options, $schema );
		}

		return new Action_Nothing();
	}

	abstract public function to_json();

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		return $columns;
	}

	public function get_results( array $results ) {
		$json = [];

		foreach ( $results['results'] as $result ) {
			$json[] = $result->to_json();
		}

		$results['results'] = $json;

		return $results;
	}

	public function get_modified_columns() {
		return [];
	}

	public function get_totals() {
		return [];
	}

	public function set_save_mode( $mode ) {
		$this->save = $mode;
	}

	public function get_view_columns() {
		return [];
	}

	public function get_replace_columns() {
		return [];
	}

	public function should_save() {
		return $this->save;
	}

	protected function log_action( $title ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore
			error_log( $title );
		}
	}

	public static function get_options( array $options ) {
		if ( ! is_array( $options ) ) {
			return [];
		}

		if ( isset( $options['action'] ) && $options['action'] === 'replace' ) {
			return [
				'search' => $options['searchPhrase'],
				'replacement' => $options['replacement'],
				'flags' => $options['searchFlags'],
			];
		}

		return isset( $options['actionOption'] ) ? $options['actionOption'] : [];
	}
}

require_once __DIR__ . '/action-modify.php';
require_once __DIR__ . '/action-nothing.php';
require_once __DIR__ . '/action-replace.php';
require_once __DIR__ . '/action-delete.php';
require_once __DIR__ . '/action-export.php';
require_once __DIR__ . '/action-run-action.php';
