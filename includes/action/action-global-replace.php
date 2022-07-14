<?php

namespace SearchRegex\Action\Type;

use SearchRegex\Schema;

/**
 * Global replace. This emulates the previous searchregex
 */
class Global_Replace extends Modify {
	/**
	 * Constructor
	 *
	 * @param array|string  $options Options.
	 * @param Schema\Schema $schema Schema.
	 */
	public function __construct( $options, Schema\Schema $schema ) {
		$converted = [];

		if ( is_array( $options ) ) {
			foreach ( $schema->get_sources() as $source ) {
				foreach ( $source->get_global_columns() as $column ) {
					$converted[] = [
						'column' => $column->get_column(),
						'source' => $source->get_type(),
						'operation' => 'replace',
						'searchValue' => $options['search'],
						'replaceValue' => $options['replacement'],
						'searchFlags' => $options['flags'],
					];
				}
			}
		}

		parent::__construct( $converted, $schema );
	}

	public function to_json() {
		return [
			'action' => 'replace',
			'actionOption' => [],
		];
	}
}
