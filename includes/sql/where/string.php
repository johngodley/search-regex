<?php

namespace SearchRegex\Sql;

use SearchRegex\Search_Flags;

/**
 * WHERE for a string
 */
class Sql_Where_String extends Sql_Where {
	/**
	 * Prefix for the value
	 *
	 * @readonly
	 * @var string
	 */
	private $before = '';

	/**
	 * Postfix for the value
	 *
	 * @readonly
	 * @var string
	 */
	private $after = '';

	/**
	 * Constructor
	 *
	 * @param Sql_Select   $column Column.
	 * @param string       $logic Logic.
	 * @param string       $value Value.
	 * @param Search_Flags $flags Search flags.
	 */
	public function __construct( Sql_Select $column, $logic, $value, Search_Flags $flags = null ) {
		if ( $flags === null ) {
			$flags = new Search_Flags( [ 'case' ] );
		}

		$logic_sql = 'LIKE';

		if ( $logic === 'notequals' || $logic === 'notcontains' ) {
			$logic_sql = 'NOT LIKE';
		}

		if ( ! $flags->is_case_insensitive() ) {
			$logic_sql .= ' BINARY';
		}

		if ( $logic === 'contains' || $logic === 'notcontains' ) {
			$this->before = '%';
			$this->after = '%';
		} elseif ( $logic === 'begins' ) {
			$this->after = '%';
		} elseif ( $logic === 'ends' ) {
			$this->before = '%';
		}

		parent::__construct( $column, $logic_sql, $value );
	}

	public function get_value() {
		global $wpdb;

		return $wpdb->prepare( '%s', $this->before . $wpdb->esc_like( $this->value ) . $this->after );
	}
}
