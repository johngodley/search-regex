<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Join;
use SearchRegex\Sql\Sql_Join_Meta;
use SearchRegex\Sql\Sql_Join_Term;
use SearchRegex\Sql\Sql_Where;
use SearchRegex\Sql\Sql_Select_Column;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_In;
use SearchRegex\Sql\Sql_Where_And;

/**
 * Filter on a member column
 */
class Search_Filter_Member extends Search_Filter_Item {
	const LOGIC = [ 'include', 'exclude' ];

	/**
	 * List of values
	 *
	 * @readonly
	 * @var list<string>
	 */
	private $values = [];

	/**
	 * Logic to filter with
	 *
	 * @readonly
	 * @var string
	 */
	private $logic = 'include';

	/**
	 * Flags
	 *
	 * @readonly
	 * @var Search_Flags
	 */
	private $flags;

	/**
	 * Join
	 *
	 * @var Sql_Join|null
	 */
	private $join = null;

	public function __construct( array $item, Schema_Column $schema ) {
		parent::__construct( $item, $schema );

		if ( isset( $item['values'] ) && is_array( $item['values'] ) && count( $item['values'] ) > 0 ) {
			$this->values = array_map( function( $item ) {
				if ( is_numeric( $item ) ) {
					return intval( $item, 10 );
				}

				return $item;
			}, $item['values'] );
		}

		if ( isset( $item['logic'] ) && in_array( strtolower( $item['logic'] ), self::LOGIC, true ) ) {
			$this->logic = strtolower( $item['logic'] );
		}

		$this->flags = new Search_Flags( isset( $item['flags'] ) ? $item['flags'] : [ 'case' ] );

		if ( $this->schema->get_join_column() ) {
			$this->join = Sql_Join::create( $this->schema->get_column(), $schema->get_source() );
		}
	}

	public function set_non_matching() {
		parent::set_non_matching();

		if ( $this->join ) {
			$this->join->set_non_matching();
		}
	}

	public function to_json() {
		return [
			'column' => $this->schema->get_column(),
			'values' => $this->values,
			'logic' => $this->logic,
			'flags' => $this->flags->to_json(),
		];
	}

	/**
	 * Get all the member values
	 *
	 * @return array
	 */
	public function get_values() {
		return $this->values;
	}

	public function is_valid() {
		return count( $this->values ) > 0 && parent::is_valid();
	}

	public function get_query() {
		$query = new Sql_Query();
		$select = new Sql_Select_Column( $this->schema );

		if ( $this->join ) {
			$query->add_join( $this->join );
		}

		if ( $this->is_valid() ) {
			$where = new Sql_Where_In( $select, $this->logic === 'exclude' ? 'NOT IN' : 'IN', $this->values );
			$query->add_where( $where );
		}

		$query->add_select( $select );
		return $query;
	}

	public function get_values_for_row( $row ) {
		$values = parent::get_values_for_row( $row );

		if ( ! $this->join instanceof Sql_Join_Meta && ! $this->join instanceof Sql_Join_Term ) {
			return $values;
		}

		if ( isset( $values[ $this->schema->get_column() ] ) && count( $values ) === 1 && $values[ $this->schema->get_column() ] === '0' ) {
			return [ $this->schema->get_column() => implode( ',', $this->join->get_all_values( intval( array_values( $row )[0], 10 ) ) ) ];
		}

		return $values;
	}

	public function get_column_data( $column, $value, Search_Source $source, Action $action ) {
		if ( $this->join ) {
			$values = explode( ',', $value );
		} else {
			$values = [ $value ];
		}

		$contexts = [];
		foreach ( $values as $value ) {
			$contexts = array_merge( $contexts, $this->get_value_context( $value, $source ) );
		}

		foreach ( $contexts as $pos => $context ) {
			$context->set_context_id( $pos );
		}

		// If everything is unmatched then return nothing, otherwise return something
		return $contexts;
	}

	/**
	 * Get context for value
	 *
	 * @param string|integer $value Value.
	 * @param Search_Source  $source Source.
	 * @return Match_Context[]
	 */
	private function get_value_context( $value, Search_Source $source ) {
		if ( is_numeric( $value ) ) {
			$value = intval( $value, 10 );
		}

		if ( count( $this->values ) > 0 ) {
			$matched = in_array( $value, $this->values, true );

			if ( $this->logic === 'exclude' && ! $matched ) {
				return $this->get_matched_context( $source, (string) $value );
			}

			if ( $this->logic === 'include' && $matched ) {
				return $this->get_matched_context( $source, (string) $value );
			}

			if ( $this->join ) {
				$label = $this->join->get_join_value( (string) $value );
				return $this->get_unmatched_context( $source, (string) $value, $label );
			}

			if ( $this->schema->get_options() ) {
				foreach ( $this->schema->get_options() as $option ) {
					if ( intval( $option['value'], 10 ) === $value ) {
						return $this->get_matched_context( $source, (string) $value, $option['label'] );
					}
				}
			}
		}

		if ( $this->join && $value === '' ) {
			return [ new Match_Context_Empty() ];
		}

		return $this->get_unmatched_context( $source, (string) $value );
	}

	public function modify_query( Sql_Query $query ) {
		if ( $this->join !== null ) {
			$join_wheres = $this->join->get_where();

			if ( $join_wheres ) {
				$where = new Sql_Where_And( array_merge( $query->get_where(), [ $join_wheres ] ) );
				$query->reset_where();
				$query->add_where( $where );
			}
		}

		return $query;
	}
}
