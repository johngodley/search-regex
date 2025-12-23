<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Action\Action;
use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Search;

class Run extends Action {
	/**
	 * Hook name
	 *
	 * @var string|false
	 */
	private $hook = false;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed>|string $options Options.
	 * @param Schema\Schema $schema Schema.
	 */
	public function __construct( $options, Schema\Schema $schema ) {
		if ( is_array( $options ) && isset( $options['hook'] ) && has_action( $options['hook'] ) ) {
			$this->hook = preg_replace( '/[A-Za-z0-9_-]/', '', $options['hook'] );
		}

		parent::__construct( $options, $schema );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_json() {
		return [
			'action' => 'action',
			'actionOption' => [
				'hook' => $this->hook,
			],
		];
	}

	/**
	 * @return bool
	 */
	public function should_save() {
		return false;
	}

	/**
	 * @param int $row_id
	 * @param array<string, mixed> $row
	 * @param Source\Source $source
	 * @param array<Search\Column> $columns
	 * @return array<Search\Column>
	 */
	public function perform( $row_id, array $row, Source\Source $source, array $columns ) {
		if ( ! $this->hook || ! $this->should_save() ) {
			return $columns;
		}

		do_action( $this->hook, $row, $row_id, $source, $columns );

		return $columns;
	}
}
