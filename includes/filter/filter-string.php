<?php

namespace SearchRegex\Filter\Type;

use SearchRegex\Sql;
use SearchRegex\Source;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Context;

/**
 * Filter a string column
 */
class Filter_String extends Filter_Type {
	const BEFORE = '<SEARCHREGEX>';
	const AFTER = '</SEARCHREGEX>';
	const LOGIC = [ 'equals', 'notequals', 'contains', 'notcontains', 'begins', 'ends' ];

	/**
	 * The value
	 *
	 * @readonly
	 * @var string
	 */
	private $value = '';

	/**
	 * Filter logic
	 *
	 * @readonly
	 * @var string
	 */
	private $logic = 'equals';

	/**
	 * Search flags
	 *
	 * @readonly
	 * @var Search\Flags
	 */
	private $flags;

	/**
	 * Does this filter have everything it needs?
	 *
	 * @var boolean
	 */
	private $has_value = false;

	public function __construct( array $item, Schema\Column $schema ) {
		parent::__construct( $item, $schema );

		if ( isset( $item['value'] ) && is_string( $item['value'] ) ) {
			$this->value = $item['value'];
			$this->has_value = true;
		}

		if ( isset( $item['logic'] ) && in_array( strtolower( $item['logic'] ), self::LOGIC, true ) ) {
			$this->logic = strtolower( $item['logic'] );
		}

		$this->flags = new Search\Flags( isset( $item['flags'] ) ? $item['flags'] : [ 'case' ] );
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
		$query = new Sql\Query();
		$select = new Sql\Select\Select_Column( $this->schema );

		$query->add_select( $select );

		if ( $this->is_valid() ) {
			$where = new Sql\Where\Where_String( $select, $this->logic, $this->value, $this->flags );
			$query->add_where( $where );
		}

		return $query;
	}

	public function get_column_data( $column, $value, Source\Source $source, Action\Action $action ) {
		return $this->get_match( $source, $action, $this->logic, $this->value, $value ? $value : '', $this->flags );
	}

	/**
	 * Match this filter against a string
	 *
	 * @param Source\Source $source Source.
	 * @param Action\Action $action Action.
	 * @param string        $logic Logic.
	 * @param string        $original_value Original value.
	 * @param string        $row_value Current value.
	 * @param Search\Flags  $flags Flags.
	 * @param array         $replacements Replacement array.
	 * @return list<Context\Context>
	 */
	public function get_match( Source\Source $source, Action\Action $action, $logic, $original_value, $row_value, Search\Flags $flags, $replacements = [] ) {
		if ( $original_value ) {
			$flag_copy = Search\Flags::copy( $flags );
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
				/** @suppress PhanTypeMismatchArgumentInternalProbablyReal */
				$value = $start . preg_quote( $original_value, null ) . $end;
			}

			// Do we have a match?
			$contexts = Search\Text::get_all( $value, $flag_copy, $replacements, $row_value );
			if ( count( $contexts ) > 0 ) {
				if ( $logic === 'notcontains' || $logic === 'notequals' ) {
					return $this->get_unmatched_context( $source, $row_value );
				}

				if ( $row_value !== '' ) {
					// Set the original flags and search terms
					return array_map( function( $context ) use ( $flags, $original_value ) {
						$context->set_search( $original_value, $flags );
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

	/**
	 * Get string value
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}
}
