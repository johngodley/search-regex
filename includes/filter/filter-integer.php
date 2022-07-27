<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Source;
use SearchRegex\Action;
use SearchRegex\Schema;

/**
 * Filter a number column.
 */
class Filter_Integer extends Filter_Type {
	const LOGIC = [ 'equals', 'notequals', 'greater', 'less', 'range', 'notrange', 'has', 'hasnot' ];

	/**
	 * Number value to filter on, or start number in a range
	 *
	 * @readonly
	 * @var integer
	 */
	protected $start_value = 0;

	/**
	 * End number in a range
	 *
	 * @readonly
	 * @var integer
	 */
	protected $end_value = 0;

	/**
	 * Logic to filter with
	 *
	 * @readonly
	 * @var string
	 */
	protected $logic = 'equals';

	/**
	 * Does this filter have all the correct values?
	 *
	 * @readonly
	 * @var boolean
	 */
	protected $has_value = false;

	/**
	 * Join object
	 *
	 * @readonly
	 * @var Sql\Join\Join|null
	 */
	protected $join = null;

	public function __construct( array $item, Schema\Column $schema ) {
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

		$joined_by = $schema->get_joined_by();
		if ( ( $this->logic === 'has' || $this->logic === 'hasnot' ) && $joined_by !== null ) {
			$this->join = Sql\Join\Join::create( $joined_by, $schema->get_source() );

			if ( $this->join && ( $this->join instanceof Sql\Join\Comment || $this->join instanceof Sql\Join\Post || $this->join instanceof Sql\Join\User ) ) {
				$this->join->set_logic( $this->logic );
				$this->has_value = true;
			}
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

	/**
	 * Get integer value
	 *
	 * @return integer
	 */
	public function get_value() {
		return $this->start_value;
	}

	public function get_query() {
		$query = new Sql\Query();
		$select = new Sql\Select\Select_Column( $this->schema );

		if ( $this->is_valid() ) {
			$where = false;

			if ( $this->logic === 'range' ) {
				$lower = new Sql\Where\Where_Integer( $select, '>=', $this->start_value );
				$upper = new Sql\Where\Where_Integer( $select, '<=', $this->end_value );

				$where = new Sql\Where\Where_And( [ $lower, $upper ] );
			} elseif ( $this->logic === 'notrange' ) {
				$lower = new Sql\Where\Where_Integer( $select, '<=', $this->start_value );
				$upper = new Sql\Where\Where_Integer( $select, '>=', $this->end_value );

				$where = new Sql\Where\Where_Or( [ $lower, $upper ] );
			} elseif ( $this->logic !== 'has' && $this->logic !== 'hasnot' ) {
				$where = new Sql\Where\Where_Integer( $select, $this->logic, $this->start_value );
			}

			if ( $this->join ) {
				$query->add_join( $this->join );
			} elseif ( $where ) {
				$query->add_where( $where );
			}
		}

		$query->add_select( $select );

		return $query;
	}

	public function get_column_data( $column, $value, Source\Source $source, Action\Action $action ) {
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
			} elseif ( $this->logic === 'has' || $this->logic === 'hasnot' ) {
				// Logic is done in SQL
				$matched = true;
			}

			if ( $matched ) {
				return $this->get_matched_context( $source, (string) $value );
			}
		}

		return $this->get_unmatched_context( $source, (string) $value );
	}

	public function modify_query( Sql\Query $query ) {
		if ( $this->join ) {
			$where = $this->join->get_where();

			if ( $where ) {
				$query->add_where( $where );
			}
		}

		return $query;
	}
}
