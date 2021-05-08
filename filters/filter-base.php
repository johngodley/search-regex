<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Schema_Column;
use SearchRegex\Action;

/**
 * A column search filter.
 */
abstract class Search_Filter_Item {
	/**
	 * Column schema
	 *
	 * @readonly
	 * @var Schema_Column
	 */
	protected $schema;

	/**
	 * Should this filter check for matches?
	 *
	 * @var boolean
	 */
	private $is_matching = true;

	/**
	 * Constructor
	 *
	 * @param array         $item   Filter item data.
	 * @param Schema_Column $schema Column schema.
	 */
	public function __construct( array $item, Schema_Column $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Mark this filter as non-matching
	 *
	 * @return void
	 */
	public function set_non_matching() {
		$this->is_matching = false;
	}

	/**
	 * Is this filter a matching filter?
	 *
	 * @return boolean
	 */
	public function is_matching() {
		return $this->is_matching;
	}

	/**
	 * Get the columns for this filter
	 *
	 * @return list<string>
	 */
	public function get_columns() {
		return [ $this->schema->get_column() ];
	}

	/**
	 * Get the actual columns for this filter
	 *
	 * @return list<string>
	 */
	public function get_actual_columns() {
		return [ $this->schema->get_column() ];
	}

	/**
	 * Get the column schema
	 *
	 * @return Schema_Column
	 */
	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Create an appropriate Search_Filter_Item object
	 *
	 * @param array         $item   Filter item data.
	 * @param Schema_Column $schema Column schema.
	 * @return Search_Filter_Item|false
	 */
	public static function create( array $item, Schema_Column $schema ) {
		if ( $schema->get_type() === 'string' ) {
			return new Search_Filter_String( $item, $schema );
		}

		if ( $schema->get_type() === 'integer' ) {
			return new Search_Filter_Integer( $item, $schema );
		}

		if ( $schema->get_type() === 'member' ) {
			return new Search_Filter_Member( $item, $schema );
		}

		if ( $schema->get_type() === 'date' ) {
			return new Search_Filter_Date( $item, $schema );
		}

		if ( $schema->get_type() === 'keyvalue' ) {
			return new Search_Filter_Keyvalue( $item, $schema );
		}

		return false;
	}

	/**
	 * Is this filter item valid?
	 *
	 * @return boolean
	 */
	public function is_valid() {
		return true;
	}

	/**
	 * Convert the filter to JSON
	 *
	 * @return array
	 */
	abstract public function to_json();

	/**
	 * Get this filter as a SQL statement. Each item within the filter is ORed together
	 *
	 * @return Sql_Query
	 */
	abstract public function get_query();

	/**
	 * Get the filter match context
	 *
	 * @param string        $column Column name.
	 * @param string        $value Row value.
	 * @param Search_Source $source Source.
	 * @param Action        $action Action.
	 * @return Match_Context[]
	 */
	abstract public function get_column_data( $column, $value, Search_Source $source, Action $action );

	/**
	 * Get a matched Match_Context
	 *
	 * @param Search_Source $source Source.
	 * @param string        $value Value.
	 * @param string        $label Label for value.
	 * @return list<Match_Context>
	 */
	public function get_matched_context( Search_Source $source, $value, $label = null ) {
		return [ new Match_Context_Matched( $value, $label ? $label : $source->convert_result_value( $this->schema, $value ) ) ];
	}

	/**
	 * Get a unmatched Match_Context
	 *
	 * @param Search_Source $source Source.
	 * @param string        $value Value.
	 * @param string        $label Label for value.
	 * @return list<Match_Context>
	 */
	public function get_unmatched_context( Search_Source $source, $value, $label = null ) {
		if ( ! $label ) {
			$label = $source->convert_result_value( $this->schema, $value );
		}

		return [ new Match_Context_Value( $value, $label ) ];
	}

	/**
	 * Does this filter require advanced searching?
	 *
	 * @return boolean
	 */
	public function is_advanced() {
		return false;
	}

	/**
	 * Can this filter affect a SUM?
	 *
	 * @return boolean
	 */
	public function can_sum_column() {
		return false;
	}

	/**
	 * Get the columns this filter uses from a row of data
	 *
	 * @param array $row Row of data from database.
	 * @return array<string,string>
	 */
	public function get_values_for_row( array $row ) {
		$values = [];

		foreach ( $this->get_actual_columns() as $column ) {
			if ( array_key_exists( $column, $row ) ) {
				$values[ $column ] = $row[ $column ];
			}
		}

		return $values;
	}

	/**
	 * Modify the query for this filter.
	 *
	 * @param Sql_Query $query Query.
	 * @return Sql_Query
	 */
	public function modify_query( Sql_Query $query ) {
		return $query;
	}
}
