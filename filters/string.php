<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Select_Column;
use SearchRegex\Sql\Sql_Select_Phrases;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Where_String;

class Search_Filter_String extends Search_Filter_Item {
	const BEFORE = '<SEARCHREGEX>';
	const AFTER = '</SEARCHREGEX>';
	const LOGIC = [ 'equals', 'notequals', 'contains', 'notcontains', 'begins', 'ends' ];

	private $value = '';
	private $logic = 'equals';
	private $flags = null;
	private $has_value = false;

	public function __construct( array $item, Schema_Column $schema ) {
		parent::__construct( $item, $schema );

		if ( isset( $item['value'] ) && is_string( $item['value'] ) ) {
			$this->value = $item['value'];
			$this->has_value = true;
		}

		if ( isset( $item['logic'] ) && in_array( strtolower( $item['logic'] ), self::LOGIC, true ) ) {
			$this->logic = strtolower( $item['logic'] );
		}

		$this->flags = new Search_Flags( isset( $item['flags'] ) ? $item['flags'] : [ 'case' ] );
	}

	public function to_json() {
		return [
			'column' => $this->schema->get_column(),
			'value' => $this->value,
			'logic' => $this->logic,
			'flags' => $this->flags->to_json(),
		];
	}

	public function is_valid() {
		return $this->logic && $this->has_value && parent::is_valid();
	}

	public function get_query() {
		$query = new Sql_Query();
		$select = new Sql_Select_Column( $this->schema );

		$query->add_select( $select );

		if ( $this->is_valid() ) {
			$where = new Sql_Where_String( $select, $this->logic, $this->value, $this->flags );

			// if ( ! $this->is_advanced() ) {
			// 	$query->add_select( new Sql_Select_Phrases( $this->schema, Sql_Value::column( $this->value ) ) );
			// }

			$query->add_where( $where );
		}

		return $query;
	}

	/**
	 * Get the filter match context
	 *
	 * @param string $row Row value
	 * @return Match_Context_String[]
	 */
	public function get_column_data( $column, $row_value, Search_Source $source, Action $action ) {
		return $this->get_match( $source, $action, $this->logic, $this->value, $row_value ? $row_value : '', $this->flags );
	}

	public function get_match( Search_Source $source, Action $action, $logic, $original_value, $row_value, $flags ) {
		if ( $original_value ) {
			$flag_copy = Search_Flags::copy( $flags );
			$value = $original_value;

			// Turn a plain match into a regex for easy matching
			if ( ! $flag_copy->is_regex() ) {
				$start = '^';
				$end = '$';

				if ( $logic === 'contains' || $logic === 'notcontains' || $logic === 'ends' ) {
					$start = '';
				}

				if ( $logic === 'contains' || $logic === 'notcontains' || $logic === 'begins' ) {
					$end = '';
				}

				$flag_copy->set_regex();
				$value = $start . preg_quote( $original_value ) . $end;
			}

			// Do we have a match?
			$contexts = Matched_Item::get_all( $value, $flag_copy, [], $row_value );
			if ( count( $contexts ) > 0 ) {
				if ( $logic === 'notcontains' || $logic === 'notequals' ) {
					return $this->get_unmatched_context( $source, $row_value );
				}

				if ( $row_value !== '' ) {
					// Set the original flags and search terms
					return array_map( function( $context ) use ( $flags, $original_value ) {
						$context->set_search( $original_value, $flags  );
						return $context;
					}, $contexts );
				}
			}

			// Return the value itself
			if ( $logic === 'notcontains' || $logic === 'notequals' ) {
				// This 'matches'
				return $this->get_matched_context( $source, $row_value );
			}
		}

		return $this->get_unmatched_context( $source, $row_value );
	}

	public function is_advanced() {
		return $this->flags->is_regex();
	}

	public function get_value() {
		return $this->value;
	}

	public function can_sum_column() {
		if ( $this->is_advanced() ) {
			return false;
		}

		if ( $this->logic === 'notequals' || $this->logic === 'notcontains' ) {
			return false;
		}

		return true;
	}
}
