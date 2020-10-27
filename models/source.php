<?php

namespace SearchRegex;

use SearchRegex\Search_Regex;

/**
 * Represents a source of data that can be searched. Typically maps directly to a database table
 */
abstract class Search_Source {
	/**
	 * The search flags
	 *
	 * @var Search_Flags
	 **/
	protected $search_flags;

	/**
	 * The source flags
	 *
	 * @var Source_Flags
	 **/
	protected $source_flags;

	/**
	 * The source type
	 *
	 * @var String
	 **/
	protected $source_type;

	/**
	 * The source type name
	 *
	 * @var String
	 **/
	protected $source_name;

	/**
	 * Create a Search_Source object
	 *
	 * @param Array        $handler Source handler information - an array of `name`, `class`, `label`, and `type`.
	 * @param Search_Flags $search_flags A Search_Flags object.
	 * @param Source_Flags $source_flags A Source_Flags object.
	 */
	public function __construct( array $handler, Search_Flags $search_flags, Source_Flags $source_flags ) {
		$this->search_flags = $search_flags;
		$this->source_flags = $source_flags;

		$this->source_type = isset( $handler['name'] ) ? $handler['name'] : 'unknown';
		$this->source_name = isset( $handler['label'] ) ? $handler['label'] : $this->source_type;
	}

	/**
	 * Return the source type
	 *
	 * @param Array $row Database row, used in some sources to determine the type.
	 * @return String Source type
	 */
	public function get_type( array $row = [] ) {
		return $this->source_type;
	}

	/**
	 * Return true if the source matches the type, false otherwise
	 *
	 * @param String $type Source type.
	 * @return boolean
	 */
	public function is_type( $type ) {
		return $this->source_type === $type;
	}

	/**
	 * Return the source name
	 *
	 * @param Array $row Database row, used in some sources to determine the type.
	 * @return String A user viewable source name
	 */
	public function get_name( array $row = [] ) {
		return $this->source_name;
	}

	/**
	 * Return the associated Source_Flags
	 *
	 * @return Source_Flags Source_Flags object
	 */
	public function get_source_flags() {
		return $this->source_flags;
	}

	/**
	 * Return an array of columns for this source
	 *
	 * @return Array The array of column names
	 */
	abstract public function get_columns();

	/**
	 * Return an array of additional columns to return in a search. These aren't searched, and can be used by the source.
	 *
	 * @return Array The array of column names
	 */
	public function get_info_columns() {
		return [];
	}

	/**
	 * Return an the table's ID column name
	 *
	 * @return String The table's ID column name
	 */
	abstract public function get_table_id();

	/**
	 * Return the column name used as a visible title for the source. For example, a post would have `post_title`
	 *
	 * @return String Column used for the title
	 */
	abstract public function get_title_column();

	/**
	 * Return the table name
	 *
	 * @return String The table name for the source
	 */
	abstract public function get_table_name();

	/**
	 * Return an array of additional search conditions applied to each query. These will be ANDed together.
	 * These conditions should be sanitized here, and won't be sanitized elsewhere.
	 *
	 * @return String SQL conditions
	 */
	public function get_search_conditions() {
		return '';
	}

	/**
	 * Return a visible label for the column. This is shown to the user and should be more descriptive than the column name itself
	 *
	 * @param String $column Column name.
	 * @param String $data Column data.
	 * @return String Column label
	 */
	public function get_column_label( $column, $data ) {
		return $column;
	}

	/**
	 * Return an array of flags used by this source via Source_Flags. Used primarily to validate the flag.
	 *
	 * @return Array The array of flags
	 */
	public function get_supported_flags() {
		return [];
	}

	/**
	 * Get an array of actions for a given row
	 *
	 * @param Result $result The Result object containing the row from the source.
	 * @return Array An array of action type => action URL
	 */
	public function get_actions( Result $result ) {
		return [];
	}

	/**
	 * Get the total number of matches for this search
	 *
	 * @param String $search Search string.
	 * @return Array{matches: int, rows: int}|\WP_Error The number of matches as an array of 'matches' and 'rows', or WP_Error on error
	 */
	public function get_total_matches( $search ) {
		global $wpdb;

		$search_query = $this->get_search_query( $search );

		// Sum all the matches
		$sum = [];
		foreach ( $this->get_columns() as $column ) {
			$cropped = mb_substr( $search, 0, mb_strlen( $search, 'UTF-8' ) - 1, 'UTF-8' );

			// phpcs:ignore
			$sum[] = $wpdb->prepare( "SUM( CHAR_LENGTH( $column ) - CHAR_LENGTH( REPLACE( UPPER($column), UPPER(%s), UPPER(%s) ) ) )", $search, $cropped );
		}

		// This is a known and validated query
		// phpcs:ignore
		$result = $wpdb->get_row( "SELECT COUNT(*) AS match_rows, " . implode( ' + ', $sum ) . " AS match_total FROM {$this->get_table_name()} WHERE " . $search_query );
		if ( $result === null ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return [
			'matches' => intval( $result->match_total, 10 ),
			'rows' => intval( $result->match_rows, 10 ),
		];
	}

	/**
	 * Get total number of rows for this source
	 *
	 * @return Int|\WP_Error The number of rows, or WP_Error on error
	 */
	public function get_total_rows() {
		global $wpdb;

		$extra = $this->get_search_conditions();
		if ( $extra ) {
			$extra = ' WHERE (' . $extra . ')';
		}

		// This is a known and validated query
		// phpcs:ignore
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->get_table_name()}" . $extra );
		if ( $result === null ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return intval( $result, 10 );
	}

	/**
	 * Get a single row from the source
	 *
	 * @param int $row_id The row ID.
	 * @return Object|\WP_Error The database row, or WP_Error on error
	 */
	public function get_row( $row_id ) {
		global $wpdb;

		$columns = $this->get_query_columns();

		// $columns, get_table_id, and get_table_name are sanitized for any user input
		// phpcs:ignore
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT {$columns} FROM {$this->get_table_name()} WHERE {$this->get_table_id()}=%d", $row_id ), ARRAY_A );
		if ( $row === null ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return $row;
	}

	/**
	 * Get all database rows for the source from the start offset to the limit
	 *
	 * @param String $search The search phrase.
	 * @param int    $offset The row offset.
	 * @param int    $limit The number of rows to return.
	 * @return Array|\WP_Error The database rows, or WP_Error on error
	 */
	public function get_all_rows( $search, $offset, $limit ) {
		global $wpdb;

		$columns = $this->get_query_columns();

		// Add any source specific conditions
		$source_conditions = $this->get_search_conditions();
		$search_phrase = '';
		if ( $source_conditions ) {
			$search_phrase = ' WHERE (' . $source_conditions . ')';
		}

		// This is a known and validated query
		// phpcs:ignore
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT {$columns} FROM {$this->get_table_name()}{$search_phrase} ORDER BY {$this->get_table_id()} ASC LIMIT %d,%d", $offset, $limit ), ARRAY_A );
		if ( $results === false || $wpdb->last_error ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return $results;
	}

	/**
	 * Get a set of matching rows
	 *
	 * @param String $search The search string.
	 * @param int    $offset The row offset.
	 * @param int    $limit The number of rows to return.
	 * @return Array|\WP_Error The database rows, or WP_Error on error
	 */
	public function get_matched_rows( $search, $offset, $limit ) {
		global $wpdb;

		$search_query = $this->get_search_query( $search );
		$columns = $this->get_query_columns();

		// This is a known and validated query
		// phpcs:ignore
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT {$columns} FROM {$this->get_table_name()} WHERE {$search_query} ORDER BY {$this->get_table_id()} ASC LIMIT %d,%d", $offset, $limit ), ARRAY_A );
		if ( $results === false || $wpdb->last_error ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return $results;
	}

	/**
	 * Get a set of matching rows from a given offset
	 *
	 * @param String $search The search string.
	 * @param int    $offset The row offset.
	 * @param int    $limit The number of rows to return.
	 * @param Bool   $exclude_search_query Exclude the search query. Used for regular expression searches.
	 * @return Array|\WP_Error The database rows, or WP_Error on error
	 */
	public function get_matched_rows_offset( $search, $offset, $limit, $exclude_search_query ) {
		global $wpdb;

		$search_query = $exclude_search_query ? $this->get_search_conditions() : $this->get_search_query( $search );
		$columns = $this->get_query_columns();

		if ( $search_query ) {
			$search_query .= ' AND ';
		}

		// phpcs:ignore
		$search_query .= $wpdb->prepare( " {$this->get_table_id() } > %d", $offset );

		// This is a known and validated query
		// phpcs:ignore
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT {$columns} FROM {$this->get_table_name()} WHERE {$search_query} ORDER BY {$this->get_table_id()} ASC LIMIT %d", $limit ), ARRAY_A );
		if ( $results === false || $wpdb->last_error ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return $results;
	}

	/**
	 * Save a replacement to the database
	 *
	 * @param int    $row_id The row ID to save.
	 * @param String $column_id The column to save.
	 * @param String $content The value to save to the column in the row.
	 * @return Bool|\WP_Error True on success, or WP_Error on error
	 */
	public function save( $row_id, $column_id, $content ) {
		global $wpdb;

		// Final check that is specific to this handler. The API check is general over all handlers
		$columns = $this->get_columns();
		if ( ! in_array( $column_id, $columns, true ) ) {
			return new \WP_Error( 'searchregex_database', 'Unknown column for database' );
		}

		$result = $wpdb->update( $this->get_table_name(), [ $column_id => $content ], [ $this->get_table_id() => $row_id ] );

		if ( $result === null ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return true;
	}

	/**
	 * Delete a row from the source
	 *
	 * @param int $row_id The row ID.
	 * @return Bool|\WP_Error true on success, or WP_Error on error
	 */
	public function delete_row( $row_id ) {
		global $wpdb;

		if ( $wpdb->delete( $this->get_table_name(), [ $this->get_table_id() => $row_id ] ) === false ) {
			return new \WP_Error( 'searchregex_database', $wpdb->last_error, 401 );
		}

		return true;
	}

	/**
	 * Returns database columns in SQL format
	 *
	 * @internal
	 * @return String SQL string
	 */
	protected function get_query_columns() {
		$columns = array_merge(
			[ $this->get_table_id() ],
			$this->get_columns(),
			$this->get_info_columns()
		);

		return implode( ', ', $columns );
	}

	/**
	 * Returns a LIKE query for a given column and search phrase
	 *
	 * @internal
	 * @param String $column Column name.
	 * @param String $search Search phrase.
	 * @return String SQL string
	 */
	protected function get_search_query_as_like( $column, $search ) {
		global $wpdb;

		if ( $this->search_flags->is_case_insensitive() ) {
			return 'UPPER(' . $column . ') ' . $wpdb->prepare( 'LIKE %s', '%' . $wpdb->esc_like( strtoupper( $search ) ) . '%' );
		}

		return $column . ' ' . $wpdb->prepare( 'LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
	}

	/**
	 * Returns the search for each of the columns in SQL format
	 *
	 * @internal
	 * @param String $search Search phrase.
	 * @return String SQL string
	 */
	protected function get_search_query( $search ) {
		$source_matches = [];

		// Look for the text in all the columns
		foreach ( $this->get_columns() as $column ) {
			$source_matches[] = $this->get_search_query_as_like( $column, $search );
		}

		// Add any source specific conditions
		$source_conditions = $this->get_search_conditions();

		$search_phrase = '(' . implode( ' OR ', $source_matches ) . ')';
		$conditions = '';
		if ( $source_conditions ) {
			$conditions = ' AND (' . $source_conditions . ')';
		}

		return $search_phrase . $conditions;
	}

	/**
	 * Get search source flags
	 *
	 * @return Search_Flags
	 */
	public function get_flags() {
		return $this->search_flags;
	}
}
