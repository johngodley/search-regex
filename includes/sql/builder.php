<?php

namespace SearchRegex\Sql;

/**
 * Perform SQL queries on the database.
 */
class Sql_Builder {
	/**
	 * Get the wpdb error
	 *
	 * @param string $sql SQL statement.
	 * @return \WP_Error
	 */
	private function get_db_error( $sql ) {
		global $wpdb;

		$error = $wpdb->last_error ? $wpdb->last_error : 'Unknown error';

		return new \WP_Error( 'searchregex_database', $error, preg_replace( '/\{.*?\}/', '%', $sql ) );
	}

	/**
	 * Get total number of matching rows from a table using the filters
	 *
	 * @param Sql_Query $query Query.
	 * @return Int|\WP_Error The number of rows, or \WP_Error on error
	 */
	public function get_count( Sql_Query $query ) {
		global $wpdb;

		$sql = $query->get_as_sql();

		$this->log_sql( 'SQL get_count', $sql );

		// This is a known and validated query
		// phpcs:ignore
		$result = $wpdb->get_var( $sql );
		if ( $result === null ) {
			return $this->get_db_error( $sql );
		}

		return intval( $result, 10 );
	}

	/**
	 * Get single row
	 *
	 * @param Sql_Query    $query Query.
	 * @param Sql_Modifier $modifier Modifier.
	 * @return object|\WP_Error
	 */
	public function get_result( Sql_Query $query, Sql_Modifier $modifier = null ) {
		global $wpdb;

		$sql = $query->get_as_sql( $modifier );

		$this->log_sql( 'SQL get_result', $sql );

		// Known query
		// phpcs:ignore
		$result = $wpdb->get_row( $sql );
		if ( $result === null ) {
			return $this->get_db_error( $sql );
		}

		return $result;
	}

	/**
	 * Get multiple rows
	 *
	 * @param Sql_Query $query Query.
	 * @return array|\WP_Error
	 */
	public function get_search( Sql_Query $query ) {
		global $wpdb;

		// phpcs:ignore
		$sql = $query->get_as_sql();
		$this->log_sql( 'SQL get_search', $sql );

		// This is a known and validated query
		// phpcs:ignore
		$results = $wpdb->get_results( $sql, ARRAY_A );
		if ( $results === false || $wpdb->last_error ) {
			return $this->get_db_error( $sql );
		}

		return $results;
	}

	/**
	 * Internal SQL debug function
	 *
	 * @param string $title Description for this SQL.
	 * @param string $query SQL.
	 * @return void
	 */
	private function log_sql( $title, $query ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! defined( 'SEARCHREGEX_TESTS' ) ) {
			// phpcs:ignore
			error_log( $title . ': ' . preg_replace( '/\{.*?\}/', '%', $query ) );
		}
	}
}
