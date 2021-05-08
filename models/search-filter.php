<?php

namespace SearchRegex;

use SearchRegex\Search_Filter_Item;
use SearchRegex\Search_Query;
use SearchRegex\Sql\Sql_Query;
use SearchRegex\Sql\Sql_Where_Or;
use SearchRegex\Action;

require_once dirname( __DIR__ ) . '/filters/filter-base.php';
require_once dirname( __DIR__ ) . '/filters/integer.php';
require_once dirname( __DIR__ ) . '/filters/member.php';
require_once dirname( __DIR__ ) . '/filters/string.php';
require_once dirname( __DIR__ ) . '/filters/date.php';
require_once dirname( __DIR__ ) . '/filters/keyvalue.php';
require_once __DIR__ . '/global-filter.php';

/**
 * Filters a search
 */
class Search_Filter {
	const MAX_OR_FILTERS = 10;  // Matches MAX_OR_FILTERS in client
	const MAX_AND_FILTERS = 20;

	/**
	 * Filters for this column, each to be ORd together
	 *
	 * @var list<Search_Filter_Item>
	 */
	protected $items = [];

	/**
	 * Column schema
	 *
	 * @readonly
	 * @var Schema_Source|null
	 */
	protected $schema = null;

	/**
	 * Create the filter
	 *
	 * @param array  $data Filter data.
	 * @param Schema $schema Schema data.
	 * @return void
	 */
	public function __construct( array $data, Schema $schema ) {
		if ( isset( $data['type'] ) ) {
			$this->schema = $schema->get_for_source( $data['type'] );
		}

		if ( isset( $data['items'] ) && $this->schema ) {
			foreach ( array_slice( $data['items'], 0, self::MAX_OR_FILTERS ) as $item ) {
				$column_schema = isset( $item['column'] ) ? $this->schema->get_column( $item['column'] ) : false;

				if ( $column_schema ) {
					$item = Search_Filter_Item::create( $item, $column_schema );

					if ( $item && $item->is_valid() ) {
						$this->items[] = $item;
					}
				}
			}
		}
	}

	/**
	 * Add a Search_Filter_Item
	 *
	 * @param Search_Filter_Item $item Item to add.
	 * @return void
	 */
	public function add_item( Search_Filter_Item $item ) {
		$this->items[] = $item;
	}

	/**
	 * Create a Search_Filter object
	 *
	 * @param array  $json JSON data.
	 * @param Schema $schema Schema for filter.
	 * @return list<Search_Filter>
	 */
	public static function create( array $json, Schema $schema ) {
		// Get basics
		$filters = array_map( function( $filter ) use ( $schema ) {
			$new_filter = new Search_Filter( $filter, $schema );

			if ( $new_filter->is_valid() ) {
				return $new_filter;
			}

			return false;
		}, array_slice( $json, 0, self::MAX_AND_FILTERS ) );

		return array_values( array_filter( $filters ) );
	}

	/**
	 * Is this filter valid?
	 *
	 * @return boolean
	 */
	public function is_valid() {
		if ( ! $this->schema ) {
			return false;
		}

		if ( count( $this->items ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Is this filter for a given source?
	 *
	 * @param string $source Source name.
	 * @return boolean
	 */
	public function is_for_source( $source ) {
		if ( $this->schema ) {
			return $this->schema->get_type() === $source;
		}

		return false;
	}

	/**
	 * Get the filter items
	 *
	 * @param Search_Source $source Optional source.
	 * @return list<Search_Filter_Item>
	 */
	public function get_items( Search_Source $source ) {
		return $this->items;
	}

	/**
	 * Does this filter have the given column?
	 *
	 * @param string|object $column Column.
	 * @param Schema_Column $schema Schema.
	 * @return boolean
	 */
	public function has_column( $column, Schema_Column $schema ) {
		$column_name = is_object( $column ) ? $column->get_column_name() : $column;

		foreach ( $this->items as $item ) {
			if ( in_array( $column_name, $item->get_actual_columns(), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all columns for this filter.
	 *
	 * @return string[]
	 */
	public function get_columns() {
		$columns = [];

		foreach ( $this->items as $item ) {
			$columns = array_merge( $columns, $item->get_columns() );
		}

		return array_unique( $columns );
	}

	/**
	 * Get array of Match_Column objects that match this filter.
	 *
	 * @param array<Match_Column> $existing Existing array.
	 * @param Search_Source       $source Source.
	 * @param array               $row Raw data from the row.
	 * @param Action              $action Action.
	 * @return array<Match_Column>
	 */
	public function get_matching_filter( array $existing, Search_Source $source, array $row, Action $action ) {
		$match_count = 0;    // The number of items that must match
		$matched_count = 0;  // The number of matching items that matched

		// At least one matching filter must match
		foreach ( $this->get_items( $source ) as $item ) {
			$match_count += $item->is_matching() ? 1 : 0;
			$column_values = $item->get_values_for_row( $row );

			foreach ( $column_values as $column => $value ) {
				$contexts = $item->get_column_data( $column, $value, $source, $action );

				if ( isset( $existing[ $column ] ) ) {
					$existing[ $column ]->add_contexts_if_matching( $contexts );
				} else {
					$existing[ $column ] = new Match_Column( $column, $source->get_column_label( $column ), $contexts, $column_values );
				}

				$matched_count += count( array_filter( $contexts, function( $item ) {
					return $item->is_matched();
				} ) );
			}
		}

		if ( ( $match_count > 0 && $matched_count > 0 ) || ( $match_count === 0 && $matched_count === 0 ) ) {
			return $existing;
		}

		return [];
	}

	/**
	 * Get an Sql_Query for this filter.
	 *
	 * @param Search_Source $source Source.
	 * @return Sql_Query
	 */
	public function get_query( Search_Source $source ) {
		$query = new Sql_Query();
		$wheres = [];

		foreach ( $this->get_items( $source ) as $filter ) {
			$new_query = $filter->get_query();

			if ( $filter->is_advanced() ) {
				$query->add_select_only( $new_query );
			} else {
				$new_query = $filter->modify_query( $new_query );

				$wheres = array_merge( $wheres, $query->add_query_except_where( $new_query ) );
			}
		}

		$query->add_where( new Sql_Where_Or( $wheres ) );
		return $query;
	}

	/**
	 * Get an array of filters as SQL. Each filter is ANDed with the next
	 *
	 * @param array<Search_Filter> $filters Filters.
	 * @param Search_Source        $source Source.
	 * @return Sql_Query
	 */
	public static function get_as_query( array $filters, Search_Source $source ) {
		$query = new Sql_Query();

		foreach ( $filters as $filter ) {
			$query->add_query( $filter->get_query( $source ) );
		}

		return $query;
	}

	/**
	 * Get the results as Matched_Items
	 *
	 * @param Search_Source $source Source.
	 * @param array         $row Row data.
	 * @param Action        $action Action.
	 * @return Match_Column[]
	 */
	public static function get_result_matches( Search_Source $source, $row, Action $action ) {
		$filters = $source->get_filters();
		$matched = [];

		// Get each filter group. There must be one 'match' in each group
		foreach ( $filters as $filter ) {
			$matched = $filter->get_matching_filter( $matched, $source, $row, $action );

			if ( count( $matched ) === 0 ) {
				return [];
			}
		}

		return $matched;
	}

	/**
	 * Is this an advanced filter?
	 *
	 * @return boolean
	 */
	public function is_advanced() {
		foreach ( $this->items as $item ) {
			if ( $item->is_advanced() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get an array of Search_Filter objects to retrieve data from any column that is otherwise missing. For example, for 'view columns'. If a column is already present then a filter will
	 * not be crearted.
	 *
	 * @param Schema               $schema Schema for all sources.
	 * @param array<Search_Filter> $filters Filters.
	 * @param array<string>        $view Array of column names in format `source__column`.
	 * @return array<Search_Filter>
	 */
	public static function get_missing_column_filters( Schema $schema, array $filters, array $view ) {
		$filter_columns = [];

		foreach ( $filters as $filter ) {
			foreach ( $filter->items as $item ) {
				if ( $filter->schema ) {
					$filter_columns[] = $filter->schema->get_type() . '__' . $item->get_schema()->get_column();
				}
			}
		}

		// Get any column that isn't defined by a filter
		$missing = array_diff( $view, $filter_columns );

		$new_filter = [];

		foreach ( $missing as $view_item ) {
			$parts = explode( '__', $view_item );

			if ( count( $parts ) === 2 ) {
				$found = false;
				$source = $schema->get_for_source( $parts[0] );
				if ( ! $source ) {
					continue;
				}

				$column = $source->get_column( $parts[1] );
				if ( ! $column ) {
					continue;
				}

				foreach ( $filters as $filter ) {
					if ( $filter->is_for_source( $parts[0] ) ) {
						if ( ! $filter->has_column( $parts[1], $column ) ) {
							// Add to existing filter for this source and column
							$new_item = Search_Filter_Item::create( [], $column );

							if ( $new_item ) {
								$new_item->set_non_matching();
								$filter->add_item( $new_item );
							}
						}

						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					if ( ! isset( $new_filter[ $parts[0] ] ) ) {
						$new_filter[ $parts[0] ] = new Search_Filter( [ 'type' => $parts[0] ], $schema );
					}

					$new_item = Search_Filter_Item::create( [], $column );

					if ( $new_item ) {
						$new_item->set_non_matching();
						$new_filter[ $parts[0] ]->add_item( $new_item );
					}
				}
			}
		}

		if ( count( $new_filter ) > 0 ) {
			return array_merge( $filters, array_values( $new_filter ) );
		}

		return $filters;
	}

	/**
	 * Convert to a JSON object
	 *
	 * @return array
	 */
	public function to_json() {
		if ( ! $this->schema ) {
			return [];
		}

		return [
			'type' => $this->schema->get_type(),
			'items' => array_map( function( $item ) {
				return $item->to_json();
			}, $this->items ),
		];
	}
}
