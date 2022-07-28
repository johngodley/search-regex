<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Source;
use SearchRegex\Context;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Filter;

/**
 * A column search filter.
 */
abstract class Filter_Type {
	/**
	 * Column schema
	 *
	 * @readonly
	 * @var Schema\Column
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
	 * @param Schema\Column $schema Column schema.
	 */
	public function __construct( array $item, Schema\Column $schema ) {
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
	 * @return Schema\Column
	 */
	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Create an appropriate Filter\Filter_Item object
	 *
	 * @param array         $item   Filter item data.
	 * @param Schema\Column $schema Column schema.
	 * @return Filter\Type\Filter_Type|false
	 */
	public static function create( array $item, Schema\Column $schema ) {
		if ( $schema->get_type() === 'string' ) {
			return new Filter_String( $item, $schema );
		}

		if ( $schema->get_type() === 'integer' ) {
			return new Filter_Integer( $item, $schema );
		}

		if ( $schema->get_type() === 'member' ) {
			return new Filter_Member( $item, $schema );
		}

		if ( $schema->get_type() === 'date' ) {
			return new Filter_Date( $item, $schema );
		}

		if ( $schema->get_type() === 'keyvalue' ) {
			return new Filter_Keyvalue( $item, $schema );
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
	 * @return Sql\Query
	 */
	abstract public function get_query();

	/**
	 * Get the filter match context
	 *
	 * @param string        $column Column name.
	 * @param string        $value Row value.
	 * @param Source\Source $source Source.
	 * @param Action\Action $action Action.
	 * @return Context\Context[]
	 */
	abstract public function get_column_data( $column, $value, Source\Source $source, Action\Action $action );

	/**
	 * Get a matched Context\Context
	 *
	 * @param Source\Source $source Source.
	 * @param string        $value Value.
	 * @param string        $label Label for value.
	 * @return list<Context\Context>
	 */
	public function get_matched_context( Source\Source $source, $value, $label = null ) {
		return [ new Context\Type\Matched( $value, $label ? $label : $source->convert_result_value( $this->schema, $value ) ) ];
	}

	/**
	 * Get a unmatched Context\Context
	 *
	 * @param Source\Source $source Source.
	 * @param string        $value Value.
	 * @param string        $label Label for value.
	 * @return list<Context\Context>
	 */
	public function get_unmatched_context( Source\Source $source, $value, $label = null ) {
		if ( ! $label ) {
			$label = $source->convert_result_value( $this->schema, $value );
		}

		return [ new Context\Type\Value( $value, $label ) ];
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
	 * @param Sql\Query $query Query.
	 * @return Sql\Query
	 */
	public function modify_query( Sql\Query $query ) {
		return $query;
	}
}
