<?php

namespace SearchRegex;

require_once __DIR__ . '/integer.php';
require_once __DIR__ . '/member.php';
require_once __DIR__ . '/string.php';
require_once __DIR__ . '/date.php';
require_once __DIR__ . '/keyvalue.php';

use SearchRegex\Sql\Sql_Query;

/**
 * A column search filter
 */
abstract class Search_Filter_Item {
	protected $schema = [];

	private $is_matching = true;

	/**
	 * Constructor
	 *
	 * @param array $item   Filter item data.
	 * @param array $schema Column schema.
	 */
	public function __construct( array $item, Schema_Column $schema ) {
		$this->schema = $schema;
	}

	public function set_non_matching() {
		$this->is_matching = false;
	}

	public function is_matching() {
		return $this->is_matching;
	}

	public function get_columns() {
		return [ $this->schema->get_column() ];
	}

	public function get_actual_columns() {
		return [ $this->schema->get_column() ];
	}

	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Create an appropriate Search_Filter_Item object
	 *
	 * @param array $item   Filter item data.
	 * @param Schema $schema Column schema.
	 * @return Search_Filter_Item
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
		return $this->schema;
	}

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
	 * @param string $row Row value
	 * @return Match_Context_String[]
	 */
	abstract public function get_column_data( $column, $value, Search_Source $source, Action $action );

	public function get_matched_context( Search_Source $source, $value, $label = null ) {
		return [ new Match_Context_Matched( $value, $label ? $label : $source->convert_result_value( $this->schema, $value ) ) ];
	}

	public function get_unmatched_context( Search_Source $source, $value, $label = null ) {
		if ( ! $label ) {
			$label = $source->convert_result_value( $this->schema, $value );
		}

		return [ new Match_Context_Value( $value, $label ) ];
	}

	public function is_advanced() {
		return false;
	}

	public function can_sum_column() {
		return false;
	}

	public function get_values_for_row( $row ) {
		$values = [];

		foreach ( $this->get_actual_columns() as $column ) {
			if ( array_key_exists( $column, $row ) ) {
				$values[ $column ] = $row[ $column ];
			}
		}

		return $values;
	}

	public function modify_query( Sql_Query $query ) {
		return $query;
	}
}
