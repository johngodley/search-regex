<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Action;
use SearchRegex\Source;
use SearchRegex\Schema;

/**
 * Filter a date column.
 */
class Filter_Date extends Filter_Type {
	const LOGIC = [ 'equals', 'notequals', 'greater', 'less', 'range' ];

	/**
	 * Date value to filter on, or start date in a range
	 *
	 * @readonly
	 * @var integer|false
	 */
	protected $start_value = false;

	/**
	 * End date value in a range
	 *
	 * @readonly
	 * @var integer|false
	 */
	protected $end_value = false;

	/**
	 * Logic to perform against the date
	 *
	 * @readonly
	 * @var string
	 */
	protected $logic = 'equals';

	/**
	 * Constructor
	 *
	 * @param array         $item JSON settings.
	 * @param Schema\Column $schema Schema.
	 */
	public function __construct( array $item, Schema\Column $schema ) {
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
		$query = new Sql\Query();
		$select = new Sql\Select\Select_Column( $this->schema );

		if ( $this->start_value !== false ) {
			if ( $this->logic === 'range' && $this->end_value !== false ) {
				$lower = new Sql\Where\Where_Date( $select, '>', $this->start_value );
				$upper = new Sql\Where\Where_Date( $select, '<', $this->end_value );

				$where = new Sql\Where\Where_And( [ $lower, $upper ] );
			} else {
				$where = new Sql\Where\Where_Date( $select, $this->logic, $this->start_value );
			}

			$query->add_where( $where );
		}

		$query->add_select( $select );

		return $query;
	}

	public function get_column_data( $column, $value, Source\Source $source, Action\Action $action ) {
		$date = mysql2date( 'U', $value );

		if ( $this->start_value !== false ) {
			$matched = false;

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
