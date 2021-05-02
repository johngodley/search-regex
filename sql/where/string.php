<?php

namespace SearchRegex\Sql;

use SearchRegex\Search_Flags;

class Sql_Where_String extends Sql_Where {
	private $before = '';
	private $after = '';

	public function __construct( Sql_Select $column, $logic, $value, \SearchRegex\Search_Flags $flags = null ) {
		global $wpdb;

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
