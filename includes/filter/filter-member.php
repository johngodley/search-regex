<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Source;
use SearchRegex\Action;
use SearchRegex\Context;
use SearchRegex\Schema;

/**
 * Filter on a member column
 */
class Filter_Member extends Filter_Type {
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
	 * Join
	 *
	 * @var Sql\Join\Join|null
	 */
	private $join = null;

	public function __construct( array $item, Schema\Column $schema ) {
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

		if ( $this->schema->get_join_column() ) {
			$this->join = Sql\Join\Join::create( $this->schema->get_column(), $schema->get_source() );
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
		$query = new Sql\Query();
		$select = new Sql\Select\Select_Column( $this->schema );

		if ( $this->join ) {
			$query->add_join( $this->join );
		}

		if ( $this->is_valid() ) {
			$where = new Sql\Where\Where_In( $select, $this->logic === 'exclude' ? 'NOT IN' : 'IN', $this->values );
			$query->add_where( $where );
		}

		$query->add_select( $select );
		return $query;
	}

	public function get_values_for_row( array $row ) {
		$values = parent::get_values_for_row( $row );

		if ( ! $this->join instanceof Sql\Join\Meta && ! $this->join instanceof Sql\Join\Term ) {
			return $values;
		}

		if ( isset( $values[ $this->schema->get_column() ] ) && count( $values ) === 1 && $values[ $this->schema->get_column() ] === '0' ) {
			return [ $this->schema->get_column() => implode( ',', $this->join->get_all_values( intval( array_values( $row )[0], 10 ) ) ) ];
		}

		return $values;
	}

	public function get_column_data( $column, $value, Source\Source $source, Action\Action $action ) {
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
	 * @param Source\Source  $source Source.
	 * @return Context\Context[]
	 */
	private function get_value_context( $value, Source\Source $source ) {
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
		}

		if ( $this->join && $value === '' ) {
			return [ new Context\Type\Empty_Type() ];
		}

		return $this->get_unmatched_context( $source, (string) $value );
	}

	public function modify_query( Sql\Query $query ) {
		if ( $this->join !== null ) {
			$join_wheres = $this->join->get_where();

			if ( $join_wheres ) {
				$where = new Sql\Where\Where_And( array_merge( $query->get_where(), [ $join_wheres ] ) );
				$query->reset_where();
				$query->add_where( $where );
			}
		}

		return $query;
	}
}
