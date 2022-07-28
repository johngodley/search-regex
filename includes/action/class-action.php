<?php

namespace SearchRegex\Action;

use SearchRegex\Action\Type;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Search;

require_once __DIR__ . '/action-nothing.php';
require_once __DIR__ . '/action-modify.php';
require_once __DIR__ . '/action-delete.php';
require_once __DIR__ . '/action-export.php';
require_once __DIR__ . '/action-run.php';
require_once __DIR__ . '/action-global-replace.php';
require_once __DIR__ . '/class-dynamic-column.php';

/**
 * Perform an action on a result
 */
abstract class Action {
	/**
	 * Schema
	 *
	 * @var Schema\Source
	 */
	protected $schema;

	/**
	 * Should this action save data to the database?
	 *
	 * @var boolean
	 */
	protected $save = false;

	/**
	 * Constructor
	 *
	 * @param array|string  $options Options.
	 * @param Schema\Schema $schema Schema.
	 */
	public function __construct( $options, Schema\Schema $schema ) {
		// Always the first one
		$this->schema = $schema->get_sources()[0];
	}

	/**
	 * Create an action
	 *
	 * @param string        $action_type Type of action.
	 * @param array|string  $action_options Array of action options.
	 * @param Schema\Schema $schema Schema for all sources.
	 * @return Action
	 */
	public static function create( $action_type, $action_options, Schema\Schema $schema ) {
		if ( $action_type === 'modify' && is_array( $action_options ) ) {
			return new Type\Modify( $action_options, $schema );
		} elseif ( $action_type === 'replace' ) {
			return new Type\Global_Replace( $action_options, $schema );
		} elseif ( $action_type === 'delete' ) {
			return new Type\Delete( $action_options, $schema );
		} elseif ( $action_type === 'export' ) {
			return new Type\Export( $action_options, $schema );
		} elseif ( $action_type === 'action' ) {
			return new Type\Run( $action_options, $schema );
		}

		return new Type\Nothing();
	}

	/**
	 * Convert action to JSON
	 *
	 * @return array
	 */
	abstract public function to_json();

	/**
	 * Perform the action
	 *
	 * @param integer              $row_id Row ID.
	 * @param array                $row Data for row.
	 * @param Source\Source        $source Source.
	 * @param array<Search\Column> $columns Contexts.
	 * @return array<Search\Column>|\WP_Error
	 */
	public function perform( $row_id, array $row, Source\Source $source, array $columns ) {
		return $columns;
	}

	/**
	 * Get the results from the action.
	 *
	 * @param array $results Results.
	 * @return array
	 */
	public function get_results( array $results ) {
		$json = [];

		foreach ( $results['results'] as $result ) {
			$json[] = $result->to_json();
		}

		$results['results'] = $json;

		return $results;
	}

	/**
	 * Change the save mode
	 *
	 * @param boolean $mode Save.
	 * @return void
	 */
	public function set_save_mode( $mode ) {
		$this->save = $mode;
	}

	/**
	 * Get any columns we need to view for this action.
	 *
	 * @return list<string>
	 */
	public function get_view_columns() {
		return [];
	}

	/**
	 * Should this action save to the database?
	 *
	 * @return boolean
	 */
	public function should_save() {
		return $this->save;
	}

	/**
	 * Debug helper function. Logs an action.
	 *
	 * @param string $title Log title.
	 * @return void
	 */
	protected function log_action( $title ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore
			error_log( $title );
		}
	}

	/**
	 * Get the action options
	 *
	 * @param string|array $options Options.
	 * @return array
	 */
	public static function get_options( $options ) {
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
