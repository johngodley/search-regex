<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Select_Column;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_Date;
use SearchRegex\Sql\Sql_Where_And;
use SearchRegex\Sql\Sql_Where_Or;

class Search_Filter_Date extends Search_Filter_Item {
	const LOGIC = [ 'equals', 'notequals', 'greater', 'less', 'range' ];

	protected $start_value = false;
	protected $end_value = false;
	protected $logic = 'equals';

	public function __construct( array $item, Schema_Column $schema ) {
		parent::__construct( $item, $schema );

		if ( isset( $item['startValue'] ) ) {
			$this->start_value = strtotime( $item['startValue'] );
		}

		if ( isset( $item['endValue'] ) ) {
			$this->end_value = strtotime( $item['endValue'] );
		}

		if ( isset( $item['logic'] ) && in_array( strtolower( $item['logic'] ), self::LOGIC, true ) ) {
			$this->logic = strtolower( $item['logic'] );
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
		if ( $this->logic === 'range' ) {
			return $this->start_value !== false && $this->end_value !== false && parent::is_valid();
		}

		return $this->start_value !== false && parent::is_valid();
	}

	public function get_query() {
		$query = new Sql_Query();
		$select = new Sql_Select_Column( $this->schema );

		if ( $this->start_value !== false ) {
			if ( $this->logic === 'range' ) {
				$lower = new Sql_Where_Date( $select, '>', $this->start_value );
				$upper = new Sql_Where_Date( $select, '<', $this->end_value );

				$where =  new Sql_Where_And( [ $lower, $upper ] );
			} elseif ( $this->logic === 'notrange' ) {
				$lower = new Sql_Where_Date( $select, '<', $this->start_value );
				$upper = new Sql_Where_Date( $select, '>', $this->end_value );

				$where = new Sql_Where_Or( [ $lower, $upper ] );
			} else {
				$where = new Sql_Where_Date( $select, $this->logic, $this->start_value );
			}

			$query->add_where( $where );
		}

		$query->add_select( $select );

		return $query;
	}

	public function get_column_data( $column, $value, Search_Source $source, Action $action ) {
		$date = mysql2date( 'U', $value );

		if ( $this->start_value !== false ) {
			if ( $this->logic === 'equals' ) {
				$matched = $date === $this->start_value;
			} elseif ( $this->logic === 'notequals' ) {
				$matched = $date !== $this->start_value;
			} elseif ( $this->logic === 'greater' ) {
				$matched = $date > $this->start_value;
			} elseif ( $this->logic === 'less' ) {
				$matched = $date < $this->start_value;
			} elseif ( $this->logic === 'range' ) {
				$matched = $date > $this->start_value && $date < $this->end_value;
			}

			if ( $matched ) {
				return $this->get_matched_context( $source, $value );
			}
		}

		return $this->get_unmatched_context( $source, $value );
	}
}
