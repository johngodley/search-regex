<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_String;

class Action_Modify_Date extends Action_Modify_Column {
	const UNITS = [ 'second', 'minute', 'hour', 'day', 'week', 'month', 'year' ];
	private $value = null;
	private $unit = null;

	public function __construct( $option, Schema_Column $schema ) {
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

	private function perform_operation( $value1, $value2 ) {
		if ( $this->operation === 'increment' ) {
			return $value1 + $value2;
		}

		return $value1 - $value2;
	}

	private function get_changed_date( $unit, $date ) {
		$hour = intval( date( 'G', $date ), 10 );
		$minute = intval( date( 'i', $date ), 10 );
		$second = intval( date( 's', $date ), 10 );
		$month = intval( date( 'n', $date ), 10 );
		$day = intval( date( 'j', $date ), 10 );
		$year = intval( date( 'Y', $date ), 10 );

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

	public function perform( $row_id, $row_value, Search_Source $source, Match_Column $column, array $raw ) {
		// Go through contexts and find the matching action that modifies it
		if ( count( $column->get_contexts() ) === 1 ) {
			$context = $column->get_contexts()[0];
			$date = $this->value;
			$value = $context->get_value();
			$mysql_date = mysql2date( 'U', $value );

			if ( $this->operation === 'increment' || $this->operation === 'decrement' ) {
				$date = $this->get_changed_date( $this->unit, $mysql_date );
			}

			if ( $date !== $value ) {
				$context->set_replacement( date( 'Y-m-d H:i:s', $date ), $source->convert_result_value( $this->schema, $date ) );
			}
		}

		return $column;
	}
}