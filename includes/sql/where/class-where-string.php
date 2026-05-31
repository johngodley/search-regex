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
	 * Get as SQL with case-sensitive comparison appropriate for the column's charset
	 *
	 * @return string
	 */
	public function get_as_sql() {
		if ( $this->column === null ) {
			return '';
		}

		$column = $this->column->get_column_or_alias();
		$logic = $this->logic;

		if ( $this->flags !== null && ! $this->flags->is_case_insensitive() ) {
			// Prefer COLLATE utf8mb4_bin so multi-byte UTF-8 characters (e.g. emojis) compare correctly.
			// On legacy databases where the column charset is still utf8 (or other non-utf8mb4),
			// utf8mb4_bin is invalid and would raise a MySQL error, so fall back to LIKE BINARY.
			if ( $this->is_utf8mb4_column() ) {
				$column .= ' COLLATE utf8mb4_bin';
			} else {
				$logic .= ' BINARY';
			}
		}

		return $column . ' ' . $logic . ' ' . $this->get_value();
	}

	/**
	 * Determine whether the underlying column is utf8mb4.
	 *
	 * Select::get_table() and get_column() preserve the original table/column metadata even after
	 * join rewriting swaps the rendered SQL to an alias such as tt.description, so joined columns
	 * can still look up their underlying charset. If the table or column genuinely can't be
	 * determined, assume non-utf8mb4 so the caller falls back to LIKE BINARY. That's the safe
	 * choice on legacy databases, because emitting COLLATE utf8mb4_bin against a utf8 column raises
	 * a MySQL error.
	 *
	 * @return bool
	 */
	private function is_utf8mb4_column(): bool {
		global $wpdb;

		if ( $this->column === null ) {
			return false;
		}

		$table = $this->column->get_table();
		$column = $this->column->get_column();

		if ( $table === '' || $column === '' ) {
			return false;
		}

		$charset = $wpdb->get_col_charset( $table, $column );

		return is_string( $charset ) && $charset === 'utf8mb4';
	}
}
