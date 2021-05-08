<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Value;

class Action_Run_Action extends Action {
	/**
	 * Hook name
	 *
	 * @var string|false
	 */
	private $hook = false;

	/**
	 * Constructor
	 *
	 * @param array|string $options Options.
	 * @param Schema       $schema Schema.
	 */
	public function __construct( $options, Schema $schema ) {
		if ( is_array( $options ) && isset( $options['hook'] ) && has_action( $options['hook'] ) ) {
			$this->hook = preg_replace( '/[A-Za-z0-9_-]/', '', $options['hook'] );
		}

		parent::__construct( $options, $schema );
	}

	public function to_json() {
		return [
			'action' => 'action',
			'actionOption' => [
				'hook' => $this->hook,
			],
		];
	}

	public function should_save() {
		return false;
	}

	public function perform( $row_id, array $row, Search_Source $source, array $columns ) {
		if ( ! $this->hook || ! $this->should_save() ) {
			return $columns;
		}

		do_action( $this->hook, $row, $row_id, $source, $columns );

		return $columns;
	}
}
