<?php

namespace SearchRegex\Sql;

require_once __DIR__ . '/query.php';
require_once __DIR__ . '/value.php';
require_once __DIR__ . '/from.php';
require_once __DIR__ . '/group.php';
require_once __DIR__ . '/join/join.php';
require_once __DIR__ . '/modifier/modifier.php';
require_once __DIR__ . '/select/select.php';
require_once __DIR__ . '/where/where.php';

class Sql_Builder {
	private function get_db_error( $sql ) {
		global $wpdb;

		return new \WP_Error( 'searchregex_database', $wpdb->last_error || 'Unknown error', preg_replace( '/\{.*?\}/', '%', $sql ) );
	}

	/**
	 * Get total number of matching rows from a table using the filters
	 *
	 * @param string   $table Table name.
	 * @param [string] $where Extra WHERE.
	 * @return Int|\WP_Error The number of rows, or WP_Error on error
	 */
	public function get_count( $query ) {
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

	public function get_result( Sql_Query $query, Sql_Modifier $modifier = null ) {
		global $wpdb;

		$sql = $query->get_as_sql( $modifier );

		$this->log_sql( 'SQL get_result', $sql );

		$result = $wpdb->get_row( $sql );
		if ( $result === null ) {
			return $this->get_db_error( $sql );
		}

		return $result;
	}

	/**
	 * Internal SQL debug function
	 *
	 * @param string $title Description for this SQL.
	 * @param string $query SQL.
	 * @return void
	 */
	private function log_sql( $title, $query ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore
			error_log( $title . ': ' . preg_replace( '/\{.*?\}/', '%', $query ) );
		}
	}

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
}
