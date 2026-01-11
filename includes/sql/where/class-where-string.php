<?php

namespace SearchRegex\Sql\Where;

use SearchRegex\Search;
use SearchRegex\Sql;

/**
 * WHERE for a string
 */
class Where_String extends Where {
	/**
	 * Prefix for the value
	 */
	private string $before = '';

	/**
	 * Postfix for the value
	 */
	private string $after = '';

	/**
	 * Search flags
	 *
	 * @var Search\Flags|null
	 */
	private ?Search\Flags $flags = null;

	/**
	 * Constructor
	 *
	 * @param Sql\Select\Select $column Column.
	 * @param string            $logic  Logic.
	 * @param string            $value  Value.
	 * @param Search\Flags|null $flags  Search flags.
	 */
	public function __construct( Sql\Select\Select $column, $logic, $value, $flags = null ) {
		if ( $flags === null ) {
			$flags = new Search\Flags( [ 'case' ] );
		}

		$this->flags = $flags;

		$logic_sql = 'LIKE';

		if ( $logic === 'notequals' || $logic === 'notcontains' ) {
			$logic_sql = 'NOT LIKE';
		}

		// Don't use LIKE BINARY for case-sensitive searches
		// BINARY performs byte-by-byte comparison which breaks multi-byte UTF-8 characters (emojis)
		// Instead, we use COLLATE utf8mb4_bin in get_as_sql()

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

	/**
	 * Get as SQL with COLLATE for case-sensitive searches
	 *
	 * @return string
	 */
	public function get_as_sql() {
		if ( $this->column !== null ) {
			$column = $this->column->get_column_or_alias();

			// Use COLLATE utf8mb4_bin for case-sensitive searches instead of LIKE BINARY
			// This properly handles multi-byte UTF-8 characters like emojis
			// WordPress has used utf8mb4 as default charset since version 4.2
			if ( $this->flags !== null && ! $this->flags->is_case_insensitive() ) {
				$column = $column . ' COLLATE utf8mb4_bin';
			}

			return $column . ' ' . $this->logic . ' ' . $this->get_value();
		}

		return '';
	}
}
