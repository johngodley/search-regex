<?php

namespace SearchRegex\Modifier\Value;

use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Modifier;
use SearchRegex\Source;
use SearchRegex\Context;

/**
 * Modify a date column
 */
class Date_Value extends Modifier\Modifier {
	const UNITS = [ 'second', 'minute', 'hour', 'day', 'week', 'month', 'year' ];

	/**
	 * Date value
	 *
	 * @var integer|null
	 */
	private $value = null;

	/**
	 * Units of the value
	 *
	 * @var 'second'|'minute'|'hour'|'day'|'week'|'month'|'year'
	 */
	private $unit = 'hour';

	public function __construct( array $option, Schema\Column $schema ) {
		parent::__construct( $option, $schema );

		$this->operation = 'set';
		if ( isset( $option['operation'] ) && in_array( $option['operation'], [ 'set', 'increment', 'decrement' ], true ) ) {
			$this->operation = $option['operation'];
		}

		if ( isset( $option['value'] ) ) {
			$this->value = $this->operation === 'set' ? strtotime( $option['value'] ) : intval( $option['value'], 10 );
		}

		if ( isset( $option['unit'] ) && in_array( $option['unit'], self::UNITS, true ) && $this->operation !== 'set' ) {
			$this->unit = $option['unit'];
		}
	}

	public function to_json() {
		return array_merge(
			parent::to_json(),
			[
				'operation' => $this->operation,
				'value' => $this->value,
				'unit' => $this->unit,
			]
		);
	}

	/**
	 * Perform a date operation - add or subtract
	 *
	 * @param integer $value1 Value 1.
	 * @param integer $value2 Value 2.
	 * @return integer
	 */
	private function perform_operation( $value1, $value2 ) {
		if ( $this->operation === 'increment' ) {
			return $value1 + $value2;
		}

		return $value1 - $value2;
	}

	/**
	 * Apply a unit calculation to a date
	 *
	 * @param string  $unit Unit.
	 * @param integer $date Date.
	 * @return integer
	 */
	private function get_changed_date( $unit, $date ) {
		$hour = intval( date( 'G', $date ), 10 );
		$minute = intval( date( 'i', $date ), 10 );
		$second = intval( date( 's', $date ), 10 );
		$month = intval( date( 'n', $date ), 10 );
		$day = intval( date( 'j', $date ), 10 );
		$year = intval( date( 'Y', $date ), 10 );

		if ( $this->value === null ) {
			return 0;
		}

		if ( $unit === 'second' ) {
			$second = $this->perform_operation( $second, $this->value );
		} elseif ( $unit === 'minute' ) {
			$minute = $this->perform_operation( $minute, $this->value );
		} elseif ( $unit === 'hour' ) {
			$hour = $this->perform_operation( $hour, $this->value );
		} elseif ( $unit === 'day' ) {
			$day = $this->perform_operation( $day, $this->value );
		} elseif ( $unit === 'week' ) {
			$day = $this->perform_operation( $day, $this->value * 7 );
		} elseif ( $unit === 'month' ) {
			$month = $this->perform_operation( $month, $this->value );
		} elseif ( $unit === 'year' ) {
			$year = $this->perform_operation( $year, $this->value );
		}

		return mktime( $hour, $minute, $second, $month, $day, $year );
	}

	public function perform( $row_id, $row_value, Source\Source $source, Search\Column $column, array $raw, $save_mode ) {
		// Go through contexts and find the matching action that modifies it
		if ( count( $column->get_contexts() ) === 1 ) {
			$context = $column->get_contexts()[0];
			if ( ! $context instanceof Context\Type\Value ) {
				return $column;
			}

			$date = $this->value;
			$value = $context->get_value();
			$mysql_date = mysql2date( 'U', (string) $value );

			if ( $this->operation === 'increment' || $this->operation === 'decrement' ) {
				$date = $this->get_changed_date( $this->unit, intval( $mysql_date, 10 ) );
			}

			if ( $date !== $value && $date !== null ) {
				$context = new Context\Type\Replace( $value );
				$date = date( 'Y-m-d H:i:s', $date );
				$context->set_replacement( $date, $source->convert_result_value( $this->schema, $date ) );
				$column->set_contexts( [ $context ] );
			}
		}

		return $column;
	}
}
