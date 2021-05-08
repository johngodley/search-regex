<?php

namespace SearchRegex;

/**
 * A global search filter that performs a string match on any column marked 'global' in the schema
 */
class Global_Search_Filter extends Search_Filter {
	/**
	 * Global search phrase
	 *
	 * @readonly
	 * @var string
	 */
	private $search_phrase = '';

	/**
	 * Global search flags
	 *
	 * @readonly
	 * @var string[]
	 */
	private $search_flags = [];

	/**
	 * Constructor
	 *
	 * @param string   $search_phrase Search.
	 * @param string[] $search_flags Search flags.
	 */
	public function __construct( $search_phrase, $search_flags ) {
		$this->search_phrase = $search_phrase;
		$this->search_flags = $search_flags;
	}

	/**
	 * Is this filter for a given source?
	 *
	 * @param string $source Source name.
	 * @return boolean
	 */
	public function is_for_source( $source ) {
		return true;
	}

	/**
	 * Is this filter for this column?
	 *
	 * @param Schema_Column $column Column.
	 * @return boolean
	 */
	public function is_for( Schema_Column $column ) {
		return $column->is_global();
	}

	/**
	 * Get the filter items
	 *
	 * @param Search_Source $source Source.
	 * @return list<Search_Filter_Item>
	 */
	public function get_items( Search_Source $source ) {
		$schema = new Schema_Source( $source->get_schema_for_source() );
		$items = [];

		foreach ( $schema->get_global_columns() as $column ) {
			$filter = [
				'column' => $column->get_column(),
				'flags' => $this->search_flags,
				'value' => $this->search_phrase,
				'logic' => 'contains',
			];

			$items[] = new Search_Filter_String( $filter, $column );
		}

		return array_merge( $items, $this->items );
	}

	public function has_column( $column, Schema_Column $schema ) {
		return $schema->is_global();
	}
}
