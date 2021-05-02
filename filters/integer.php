<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Select_Column;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_Integer;
use SearchRegex\Sql\Sql_Where_And;
use SearchRegex\Sql\Sql_Where_Or;
use SearchRegex\Sql\Sql_Join;

class Search_Filter_Integer extends Search_Filter_Item {
	const LOGIC = [ 'equals', 'notequals', 'greater', 'less', 'range', 'has', 'hasnot' ];

	protected $start_value = 0;
	protected $end_value = 0;
	protected $logic = 'equals';
	protected $has_value = false;
	protected $join = null;

	public function __construct( array $item, Schema_Column $schema ) {
		parent::__construct( $item, $schema );

		if ( isset( $item['startValue'] ) && $item['startValue'] !== '' ) {
			$this->start_value = intval( $item['startValue'], 10 );
			$this->has_value = true;
		}

		if ( isset( $item['endValue'] ) && $item['endValue'] !== '' ) {
			$this->end_value = max( intval( $item['endValue'], 10 ), $this->start_value );
			$this->has_value = true;
		}

		if ( isset( $item['logic'] ) && in_array( strtolower( $item['logic'] ), self::LOGIC, true ) ) {
			$this->logic = strtolower( $item['logic'] );
		}

		if ( ( $this->logic === 'has' || $this->logic === 'hasnot' ) && $schema->get_joined_by() ) {
			$this->join = Sql_Join::create( $schema->get_joined_by(), $schema->get_source() );
			$this->join->set_logic( $this->logic );
			$this->has_value = true;
		}
	}

	public function to_json() {
		return [
			'column' => $this->schema->get_column(),
			'startValue' => $this->start_value,
			'endValue' => $this->end_value,
			'logic' => $this->logic,
		];
	}

	public function is_valid() {
		return $this->has_value;
	}

	public function get_value() {
		return $this->start_value;
	}

	public function get_query() {
		$query = new Sql_Query();
		$select = new Sql_Select_Column( $this->schema );

		if ( $this->is_valid() ) {
			if ( $this->logic === 'range' ) {
				$lower = new Sql_Where_Integer( $select, '>=', $this->start_value );
				$upper = new Sql_Where_Integer( $select, '<=', $this->end_value );

				$where =  new Sql_Where_And( [ $lower, $upper ] );
			} elseif ( $this->logic === 'notrange' ) {
				$lower = new Sql_Where_Integer( $select, '<=', $this->start_value );
				$upper = new Sql_Where_Integer( $select, '>=', $this->end_value );

				$where = new Sql_Where_Or( [ $lower, $upper ] );
			} elseif ( $this->logic !== 'has' && $this->logic !== 'hasnot' ) {
				$where = new Sql_Where_Integer( $select, $this->logic, $this->start_value );
			}

			if ( $this->join ) {
				$query->add_join( $this->join );
			} else {
				$query->add_where( $where );
			}
		}

		$query->add_select( $select );

		return $query;
	}

	public function get_column_data( $column, $value, Search_Source $source, Action $action ) {
		$value = intval( $value, 10 );

		if ( $this->has_value ) {
			$matched = false;

			if ( $this->logic === 'equals' ) {
				$matched = $this->start_value === $value;
			} elseif ( $this->logic === 'notequals' ) {
				$matched = $this->start_value !== $value;
			} elseif ( $this->logic === 'greater' ) {
				$matched = $value > $this->start_value;
			} elseif ( $this->logic === 'less' ) {
				$matched = $value < $this->start_value;
			} elseif ( $this->logic === 'range' ) {
				$matched = $value >= $this->start_value && $value <= $this->end_value;
			} elseif ( $this->logic === 'notrange' ) {
				$matched = $value <= $this->start_value || $value >= $this->end_value;
			} elseif ( $this->logic === 'has' ) {
				// XXX
				$matched = true;
//				$matched = $value !== 0;
			} elseif ( $this->logic === 'hasnot' ) {
				$matched = true;
//				$matched = $value === 0;
			}

			if ( $matched ) {
				return $this->get_matched_context( $source, $value );
			}
		}

		return $this->get_unmatched_context( $source, $value );
	}

	public function modify_query( Sql_Query $query ) {
		if ( $this->join ) {
			$query->add_where( $this->join->get_where() );
		}

		return $query;
	}
}
