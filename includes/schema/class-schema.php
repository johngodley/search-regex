<?php

namespace SearchRegex\Schema;

require_once __DIR__ . '/class-source.php';
require_once __DIR__ . '/class-column.php';

/**
 * Helper object to load a source schema and provide access functions
 */
class Schema {
	/**
	 * Array of Source objects
	 *
	 * @var array<string, Source>
	 */
	private $schema = [];

	/**
	 * Constructor
	 *
	 * @param array $source_schema JSON schema.
	 */
	public function __construct( array $source_schema ) {
		foreach ( $source_schema as $schema ) {
			$schema = new Source( $schema );

			if ( $schema->get_type() ) {
				$this->schema[ $schema->get_type() ] = $schema;
			}
		}
	}

	/**
	 * Get Source for a source.
	 *
	 * @param string $source_name Source name.
	 * @return Source|null
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
	 * @return list<Source>
	 */
	public function get_sources() {
		return array_values( $this->schema );
	}
}
