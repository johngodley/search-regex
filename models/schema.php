<?php

namespace SearchRegex;

require_once __DIR__ . '/schema-source.php';
require_once __DIR__ . '/schema-column.php';

/**
 * Helper object to load a source schema and provide access functions
 */
class Schema {
	/**
	 * Array of Schema_Source objects
	 *
	 * @var array<string, Schema_Source>
	 */
	private $schema = [];

	/**
	 * Constructor
	 *
	 * @param array $source_schema JSON schema.
	 */
	public function __construct( array $source_schema ) {
		foreach ( $source_schema as $schema ) {
			$schema = new Schema_Source( $schema );

			if ( $schema->get_type() ) {
				$this->schema[ $schema->get_type() ] = $schema;
			}
		}
	}

	/**
	 * Get Schema_Source for a source.
	 *
	 * @param string $source_name Source name.
	 * @return Schema_Source|null
	 */
	public function get_for_source( $source_name ) {
		if ( isset( $this->schema[ $source_name ] ) ) {
			return $this->schema[ $source_name ];
		}

		return null;
	}

	/**
	 * Get list of all sources
	 *
	 * @return list<Schema_Source>
	 */
	public function get_sources() {
		return array_values( $this->schema );
	}
}
