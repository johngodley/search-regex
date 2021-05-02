<?php

namespace SearchRegex;

class Action_Global_Replace extends Action_Modify {
	public function __construct( array $options, Schema $schema ) {
		$converted = [];

		foreach ( $schema->get_sources() as $source ) {
			foreach ( $source->get_global_columns() as $column ) {
				$converted[] = [
					'column' => $column->get_column(),
					'source' => $source->get_type(),
					'operation' => in_array( 'regex', $options['flags'], true ) ? 'set' : 'replace',
					'searchValue' => $options['search'],
					'replaceValue' => $options['replacement'],
					'searchFlags' => $options['flags'],
				];
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
