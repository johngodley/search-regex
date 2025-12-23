<?php

namespace SearchRegex\Schema;

/**
 * Helper object to load a source schema and provide access functions
 *
 * @phpstan-import-type SourceSchemaJson from Source
 */
class Schema {
	/**
	 * Array of Source objects
	 *
	 * @var array<string, Source>
	 */
	private array $schema = [];

	/**
	 * Constructor
	 *
	 * @param list<SourceSchemaJson> $source_schema JSON schema.
	 */
	public function __construct( $source_schema ) {
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
		return $this->schema[ $source_name ] ?? null;
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
