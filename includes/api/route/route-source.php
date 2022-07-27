<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Source;
use SearchRegex\Api;

/**
 * @apiDefine ColumnData Data for a column
 * Data for a column. Each item of data contains either `value` or `items`, but not both.
 *
 * @apiSuccess {Object[]} column - Column data
 * @apiSuccess {String} column.column - Name of the column
 * @apiSuccess {String} [column.value] - Column value
 * @apiSuccess {Object[]} [column.items] - Array of key/value pairs
 * @apiSuccess {String} column.items.key - Key value
 * @apiSuccess {String} column.items.value - Value
 * @apiSuccess {String} column.items.value_type - Type of the value
 */

/**
 * @api {get} /search-regex/v1/source List of sources
 * @apiVersion 1.0.0
 * @apiName GetSources
 * @apiDescription Return all the available sources
 *
 * @apiGroup Source
 *
 * @apiUse SearchResults
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {get} /search-regex/v1/source/:source/:rowId Load row
 * @apiName LoadRow
 * @apiDescription Load a row of data from one source. This can be used to get the full data for a particular search.
 *
 * @apiGroup Source
 *
 * @apiParam (URL) {String} :source The source
 * @apiParam (URL) {Integer} :rowId The source row ID
 *
 * @apiUse ColumnData
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/source/:source/:rowId Save row
 * @apiName SaveRow
 * @apiDescription Save a row of data to a source.
 *
 * @apiGroup Source
 *
 * @apiParam (URL) {String} :source The source
 * @apiParam (URL) {Integer} :rowId The source row ID
 * @apiParam (Post Data) {Object} replacement The replacement data
 * @apiParam (Post Data) {String} replacement.column The column name to perform a replace in
 * @apiParam (Post Data) {String} [replacement.operation] Operation appropriate to the type of column (i.e. 'set').
 * @apiParam (Post Data) {String[]} [replacement.values] Values for the operation
 * @apiParam (Post Data) {String[]} [replacement.searchValue] Search value
 * @apiParam (Post Data) {String[]} [replacement.replaceValue] Replace value
 * @apiParam (Post Data) {Object[]} [replacement.items] Array of replacement items
 * @apiParam (Post Data) {String} [replacement.items.type] Type of replacement
 * @apiParam (Post Data) {String} [replacement.items.key] Key
 * @apiParam (Post Data) {String} [replacement.items.value] Value
 *
 * @apiUse SearchResult
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/source/:source/:rowId/delete Delete row
 * @apiVersion 1.0.0
 * @apiName DeleteRow
 * @apiDescription Removes an entire row of data from the source
 *
 * @apiGroup Source
 *
 * @apiParam (URL) {String} :source The source
 * @apiParam (URL) {Integer} :rowId The source row ID
 * @apiParam (Search Query) {Integer} page Page to search
 * @apiParam (Search Query) {Integer} perPage Number of results per page
 * @apiParam (Search Query) {String="forward","backward"} [searchDirection=forward] Direction to search. Only needed for regular expression searches
 *
 * @apiSuccess {Bool} result `true` if deleted, `false` otherwise
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * Search API endpoint
 */
class Source_Route extends Api\Route {
	const AUTOCOMPLETE_MAX = 50;
	const AUTOCOMPLETE_TRIM_BEFORE = 10;

	/**
	 * API schema for source validation.
	 *
	 * @return array
	 */
	private function get_source_params() {
		return [
			'source' => [
				'validate_callback' => [ $this, 'validate_source' ],
				'sanitize_callback' => [ $this, 'sanitize_row_source' ],
			],
		];
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/source', [
			$this->get_route( \WP_REST_Server::READABLE, 'getSources', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/complete/(?P<column>[a-z\-\_]+)', [
			'args' => array_merge(
				$this->get_source_params(),
				[
					'value' => [
						'description' => 'Auto complete value',
						'type' => 'string',
						'required' => true,
					],
				]
			),
			$this->get_route( \WP_REST_Server::READABLE, 'autoComplete', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)', [
			'args' => $this->get_source_params(),
			$this->get_route( \WP_REST_Server::READABLE, 'loadRow', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)', [
			'args' => array_merge(
				[
					'replacement' => [
						'description' => 'Row replacement. A single action.',
						'type' => 'object',
						'validate_callback' => [ $this, 'validate_replacement' ],
						'required' => true,
					],
				],
				$this->get_source_params(),
				$this->get_search_params()
			),
			$this->get_route( \WP_REST_Server::EDITABLE, 'saveRow', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)/delete', [
			'args' => $this->get_source_params(),
			$this->get_route( \WP_REST_Server::EDITABLE, 'deleteRow', [ $this, 'permission_callback' ] ),
		] );
	}

	/**
	 * Sanitize the source so it's a single source in an array, suitable for use with the search functions
	 *
	 * @param string|array     $value Source name.
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $param Param name.
	 * @return string[]
	 */
	public function sanitize_row_source( $value, \WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			return array_slice( $value, 0, 1 );
		}

		return [ $value ];
	}

	/**
	 * Validate the replacement.
	 *
	 * @param string|array     $value Source name.
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $param Param name.
	 * @return true|\WP_Error
	 */
	public function validate_replacement( $value, \WP_REST_Request $request, $param ) {
		$result = $this->contains_keys( [ 'column' ], $value );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $value['items'] ) ) {
			foreach ( $value['items'] as $item ) {
				$result = $this->contains_keys( [ 'value', 'key' ], $item );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		if ( isset( $value['values'] ) && ! is_array( $value['values'] ) ) {
			return new \WP_Error( 'rest_invalid_param', 'Item is not an array', [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Get list of all sources
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_Error|array Return an array of sources, or a \WP_Error
	 */
	public function getSources( \WP_REST_Request $request ) {
		$sources = Source\Manager::get_all_sources();

		return array_map( function( $source ) {
			return [
				'name' => $source['name'],
				'label' => $source['label'],
				'description' => $source['description'],
				'type' => $source['type'],
			];
		}, $sources );
	}

	/**
	 * Perform a replacement on a row
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_Error|array Return an array of results, or a \WP_Error
	 */
	public function saveRow( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$replace = [
			'action' => 'modify',
			'actionOption' => [
				$params['replacement'],
			],
		];

		list( $search, $action ) = $this->get_search_replace( array_merge( $params, $replace ) );

		// Get the results for the search/replace
		$results = $search->get_row( $params['rowId'], $action );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		if ( count( $results ) === 0 ) {
			return new \WP_Error( 'rest_invalid_param', 'No matching row', [ 'status' => 400 ] );
		}

		// Save the changes
		$results = $search->save_changes( $results[0] );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		// Get the row again, with the original search conditions
		list( $search, $action ) = $this->get_search_replace( $params );
		$results = $search->get_row( $params['rowId'], $action );
		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		return [
			'result' => $action->get_results( [ 'results' => $results ] )['results'][0],
		];
	}

	/**
	 * Load all relevant data from a source's row
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_Error|array Return an array of results, or a \WP_Error
	 */
	public function loadRow( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );
		$row = $sources[0]->get_row_columns( $params['rowId'] );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		return [
			'result' => $row,
		];
	}

	/**
	 * Delete a row of data
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_Error|array Return an array of results, or a \WP_Error
	 */
	public function deleteRow( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );

		return $sources[0]->delete_row( $params['rowId'] );
	}

	/**
	 * Autocomplete some value and return suggestions appropriate to the source and column
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_Error|array Return an array of results, or a \WP_Error
	 */
	public function autoComplete( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );

		// Validate the column
		foreach ( $sources[0]->get_schema_for_source()['columns'] as $column ) {
			if ( $column['column'] === $params['column'] && $column['options'] === 'api' ) {
				// Get autocomplete results
				$rows = $sources[0]->autocomplete( $column, $params['value'] );
				$results = [];

				foreach ( $rows as $row ) {
					$result = [
						'value' => $row->id,
						'title' => str_replace( [ '\n', '\r', '\t' ], '', $row->value ),
					];

					// Trim content to context
					if ( strlen( $result['title'] ) > self::AUTOCOMPLETE_MAX && strlen( $params['value'] ) > 0 ) {
						$pos = strpos( $result['title'], $params['value'] );

						if ( $pos !== false ) {
							$result['title'] = '...' . substr( $result['title'], max( 0, $pos - self::AUTOCOMPLETE_TRIM_BEFORE ), self::AUTOCOMPLETE_MAX ) . '...';
						}
					} elseif ( strlen( $result['title'] ) > self::AUTOCOMPLETE_MAX ) {
						$result['title'] = substr( $result['title'], 0, self::AUTOCOMPLETE_MAX ) . '...';
					}

					$results[] = $result;
				}

				// Return to user
				return apply_filters( 'searchregex_autocomplete_results', $results );
			}
		}

		// Invalid column
		return new \WP_Error( 'rest_invalid_param', 'Unknown column ' . $params['column'], [ 'status' => 400 ] );
	}
}
