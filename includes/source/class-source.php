<?php

namespace SearchRegex\Source;

use SearchRegex\Sql;
use SearchRegex\Schema;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Context;

require_once __DIR__ . '/class-manager.php';
require_once __DIR__ . '/class-convert-values.php';
require_once __DIR__ . '/class-autocomplete.php';
require_once __DIR__ . '/trait-has-meta.php';
require_once __DIR__ . '/trait-has-terms.php';
require_once __DIR__ . '/core/source-meta.php';
require_once __DIR__ . '/core/source-post.php';
require_once __DIR__ . '/core/source-post-meta.php';
require_once __DIR__ . '/core/source-user.php';
require_once __DIR__ . '/core/source-user-meta.php';
require_once __DIR__ . '/core/source-terms.php';
require_once __DIR__ . '/core/source-comment.php';
require_once __DIR__ . '/core/source-comment-meta.php';
require_once __DIR__ . '/core/source-options.php';

/**
 * Represents a source of data that can be searched. Typically maps directly to a database table
 */
abstract class Source {
	const AUTOCOMPLETE_LIMIT = 50;

	/**
	 * Search filters
	 *
	 * @var array<Filter\Filter>
	 */
	protected $filters;

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
	 * Create a Source\Source object
	 *
	 * @param Array                $handler Source handler information - an array of `name`, `class`, `label`, and `type`.
	 * @param array<Filter\Filter> $filters Array of Filter\Filter objects for this source.
	 */
	public function __construct( array $handler, array $filters ) {
		$this->filters = $filters;
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
	 * Return the associated Filter\Filter items
	 *
	 * @return list<Filter\Filter> Filter\Filter objects
	 */
	public function get_search_filters() {
		return $this->filters;
	}

	/**
	 * Return an array of additional columns to return in a search. These aren't searched, and can be used by the source.
	 *
	 * @return Array The array of column names
	 */
	public function get_info_columns() {
		return [
			new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( $this->get_title_column() ) ),
		];
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
	 * Return a visible label for the column. This is shown to the user and should be more descriptive than the column name itself
	 *
	 * @param String $column Column name.
	 * @return String Column label
	 */
	public function get_column_label( $column ) {
		foreach ( $this->get_schema()['columns'] as $schema_column ) {
			if ( $schema_column['column'] === $column ) {
				return $schema_column['title'];
			}
		}

		return $column;
	}

	/**
	 * Get an array of actions for a given row
	 *
	 * @param Search\Result $result The Search\Result object containing the row from the source.
	 * @return Array An array of action type => action URL
	 */
	public function get_actions( Search\Result $result ) {
		return [];
	}

	/**
	 * Get the total number of matches for this search
	 *
	 * @param Filter\Filter[] $filters Search string.
	 * @return Array{matches: int, rows: int}|\WP_Error The number of matches as an array of 'matches' and 'rows', or \WP_Error on error
	 */
	public function get_global_match_total( array $filters ) {
		$query = Filter\Filter::get_as_query( $filters, $this );
		$query->add_from( new Sql\From( Sql\Value::column( $this->get_table_name() ) ) );

		$sql = new Sql\Builder();

		$result = $sql->get_result( $query, new Sql\Modifier\Select_Count_Id( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( $this->get_table_id() ) ) );
		if ( $result instanceof \WP_Error ) {
			return $result;
		}

		return [
			'matches' => isset( $result->match_total ) ? intval( $result->match_total, 10 ) : 0,
			'rows' => intval( $result->match_rows, 10 ),
		];
	}

	/**
	 * Get total number of rows for this source
	 *
	 * @return Int|\WP_Error The number of rows, or \WP_Error on error
	 */
	public function get_total_rows() {
		$sql = new Sql\Builder();

		$query = new Sql\Query();
		$query->add_select( new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::safe_raw( 'COUNT(*)' ) ) );
		$query->add_from( new Sql\From( Sql\Value::column( $this->get_table_name() ) ) );

		return $sql->get_count( $query );
	}

	/**
	 * Get a single row from the source
	 *
	 * @param int $row_id The row ID.
	 * @return array|\WP_Error The database row, or \WP_Error on error
	 */
	public function get_row( $row_id ) {
		$builder = new Sql\Builder();

		// Create query
		$query = new Sql\Query();
		$query->add_selects( $this->get_query_selects() );
		$query->add_from( new Sql\From( Sql\Value::column( $this->get_table_name() ) ) );

		// Add the filters except the where
		$query->add_query_except_where( Filter\Filter::get_as_query( $this->filters, $this ) );

		// Add our row ID
		$query->add_where( new Sql\Where\Where_Integer( new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( $this->get_table_id() ) ), 'equals', $row_id ) );

		return $builder->get_search( $query );
	}

	/**
	 * Get columns for a single row
	 *
	 * @param int $row_id The row ID.
	 * @return array|\WP_Error
	 */
	public function get_row_columns( $row_id ) {
		global $wpdb;

		$columns = array_filter( $this->get_schema()['columns'], function( $column ) {
			if ( isset( $column['join'] ) ) {
				return false;
			}

			if ( isset( $column['modify'] ) && $column['modify'] === false ) {
				return false;
			}

			return true;
		} );

		$columns = array_map( function( $column ) {
			return $column['column'];
		}, $columns );

		// Known query
		// phpcs:ignore
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT " . implode( ',', $columns ) . " FROM {$this->get_table_name()} WHERE {$this->get_table_id()}=%d", $row_id ), ARRAY_A );
		if ( $row === null ) {
			return new \WP_Error( 'searchregex_database', 'No row for ' . (string) $row_id, 401 );
		}

		// Convert it, then get other stuff
		$row_columns = [];
		foreach ( $row as $column => $value ) {
			$row_columns[] = [
				'column' => $column,
				'value' => $value,
			];
		}

		return $row_columns;
	}

	/**
	 * Get a set of matching rows
	 *
	 * @param int $offset The row offset.
	 * @param int $limit The number of rows to return.
	 * @return Array|\WP_Error The database rows, or \WP_Error on error
	 */
	public function get_matched_rows( $offset, $limit ) {
		$builder = new Sql\Builder();

		// Create query
		$query = new Sql\Query();
		$query->add_selects( $this->get_query_selects() );
		$query->add_from( new Sql\From( Sql\Value::column( $this->get_table_name() ) ) );
		$query->set_paging( $offset, $limit );
		$query->set_order( $this->get_table_name() . '.' . $this->get_table_id() );

		// Add filters
		$query->add_query( Filter\Filter::get_as_query( $this->filters, $this ) );

		return $builder->get_search( $query );
	}

	/**
	 * Can we replace this column?
	 *
	 * @param string $column Column name.
	 * @return boolean
	 */
	private function can_replace_column( $column ) {
		foreach ( $this->get_schema()['columns'] as $column_schema ) {
			if ( $column_schema['column'] === $column ) {
				return ! isset( $column_schema['modify'] ) || $column_schema['modify'];
			}
		}

		return false;
	}

	/**
	 * Get array of columns to change
	 *
	 * @param array $updates Array of updates.
	 * @return array Array of column name => replacement
	 */
	protected function get_columns_to_change( array $updates ) {
		$columns = [];

		foreach ( $updates as $column => $update ) {
			foreach ( $update['change'] as $change ) {
				if ( $change->get_type() === Context\Type\Replace::TYPE_REPLACE && $this->can_replace_column( $column ) ) {
					$columns[ $column ] = $change->get_replacement();
				}
			}
		}

		return $columns;
	}

	/**
	 * Save a replacement to the database
	 *
	 * @param int   $row_id The row ID to save.
	 * @param array $changes The value to save to the column in the row.
	 * @return Bool|\WP_Error True on success, or \WP_Error on error
	 */
	abstract public function save( $row_id, array $changes );

	/**
	 * Delete a row from the source
	 *
	 * @param int $row_id The row ID.
	 * @return Bool|\WP_Error true on success, or \WP_Error on error
	 */
	abstract public function delete_row( $row_id );

	/**
	 * Returns database columns in SQL format
	 *
	 * @internal
	 * @return Sql\Select\Select[] SQL string
	 */
	protected function get_query_selects() {
		return array_merge(
			// Table ID column
			[ new Sql\Select\Select( Sql\Value::table( $this->get_table_name() ), Sql\Value::column( $this->get_table_id() ) ) ],
			// Any extra 'info' columns
			$this->get_info_columns()
		);
	}

	/**
	 * Get source filters
	 *
	 * @return Filter\Filter[]
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Get schema for a source
	 *
	 * @return array
	 */
	public function get_schema_for_source() {
		return apply_filters( 'searchregex_schema_item', $this->get_schema() );
	}

	/**
	 * Get the columns in the order they are defined in the schema, suitable for ordering results
	 *
	 * @return array
	 */
	public function get_schema_order() {
		$schema = $this->get_schema_for_source();
		$values = range( 0, count( $schema['columns'] ) - 1 );
		$keys = array_map( function( $column ) {
			return $column['column'];
		}, $schema['columns'] );

		return array_combine( $keys, $values );
	}

	/**
	 * Internal function to get schema, which is then filtered by `get_schema_for_source`
	 *
	 * @return array
	 */
	abstract protected function get_schema();

	/**
	 * Get the schema as a Schema\Source object
	 *
	 * @return Schema\Source
	 */
	public function get_schema_item() {
		return new Schema\Source( $this->get_schema() );
	}

	/**
	 * Get any preloadable data for the given filter
	 *
	 * @param array         $schema Schema.
	 * @param Filter\Filter $filter Filter.
	 * @return array
	 */
	public function get_filter_preload( $schema, $filter ) {
		return [];
	}

	/**
	 * Perform autocompletion on a column and a value
	 *
	 * @param array  $column Column.
	 * @param string $value  Value.
	 * @return array
	 */
	abstract public function autocomplete( array $column, $value );

	/**
	 * Does this source have any advanced filters?
	 *
	 * @return boolean
	 */
	public function has_advanced_filter() {
		foreach ( $this->filters as $filter ) {
			if ( $filter->is_advanced() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Try and convert the column value into a text label. For example, user ID to user name
	 *
	 * @param Schema\Column $schema Schema.
	 * @param string        $value Column value.
	 * @return string Column label, or column value.
	 */
	public function convert_result_value( Schema\Column $schema, $value ) {
		if ( $schema->get_options() ) {
			foreach ( $schema->get_options() as $option ) {
				/** @psalm-suppress DocblockTypeContradiction */
				if ( $option['value'] === $value || intval( $option['value'], 10 ) === $value ) {
					return $option['label'];
				}
			}
		}

		if ( $schema->get_source() ) {
			$convert = new Convert_Values();

			return $convert->convert( $schema, $value );
		}

		return $value;
	}

	/**
	 * Helper function to log actions if WP_DEBUG is enabled
	 *
	 * @param string               $title Log title.
	 * @param array|string|integer $update Log data.
	 * @return void
	 */
	protected function log_save( $title, $update ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore
			error_log( $title . ': ' . print_r( $update, true ) );
		}
	}
}
