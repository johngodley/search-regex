<?php

namespace SearchRegex\Filter;

use SearchRegex\Schema;
use SearchRegex\Source;
use SearchRegex\Filter\Type;

/**
 * A global search filter that performs a string match on any column marked 'global' in the schema
 */
class Global_Filter extends Filter {
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
	 * @param Schema\Column $column Column.
	 * @return boolean
	 */
	public function is_for( Schema\Column $column ) {
		return $column->is_global();
	}

	/**
	 * Get the filter items
	 *
	 * @param Source\Source $source Source.
	 * @return list<Type\Filter_Type>
	 */
	public function get_items( Source\Source $source ) {
		$schema = new Schema\Source( $source->get_schema_for_source() );
		$items = [];

		foreach ( $schema->get_global_columns() as $column ) {
			$filter = [
				'column' => $column->get_column(),
				'flags' => $this->search_flags,
				'value' => $this->search_phrase,
				'logic' => 'contains',
			];

			$items[] = new Type\Filter_String( $filter, $column );
		}

		return array_merge( $items, array_filter( $this->items, function( $item ) use ( $source ) {
			return $item->get_schema()->get_type() === $source->get_type();
		} ) );
	}

	public function has_column( $column, Schema\Column $schema ) {
		return $schema->is_global();
	}

	public function is_advanced() {
		return in_array( 'regex', $this->search_flags, true );
	}
}
