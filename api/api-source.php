<?php

use SearchRegex\Search;
use SearchRegex\Replace;
use SearchRegex\Search_Flags;
use SearchRegex\Search_Source;
use SearchRegex\Source_Manager;
use SearchRegex\Source_Flags;

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
 * @apiSuccess {String[]} result Associative array of `column_name` => `value`
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/source/:source/:rowId Update row
 * @apiVersion 1.0.0
 * @apiName UpdateRow
 * @apiDescription Save data to a column of a row of a source, returning the same row back with modified data
 *
 * @apiGroup Source
 *
 * @apiParam (URL) {String} :source The source
 * @apiParam (URL) {Integer} :rowId The source row ID
 * @apiParam {String} columnId Column ID of the item to save content to
 * @apiParam {String} content The new content
 *
 * @apiUse SearchResult
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/source/:source/:rowId/replace Replace a row
 * @apiVersion 1.0.0
 * @apiName ReplaceRow
 * @apiDescription Performs a replace on a row
 *
 * @apiGroup Source
 *
 * @apiParam (URL) {String} :source The source
 * @apiParam {Integer} :rowId Row ID of the item to replace
 * @apiParam {String} [columnId] Column ID of the item to replace
 * @apiParam {Integer} [posId] Positional ID of the item to replace
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
 *
 * @apiSuccess {Bool} result `true` if deleted, `false` otherwise
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * Search API endpoint
 */
class Search_Regex_Api_Source extends Search_Regex_Api_Route {
	/**
	 * Search API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/source', [
			$this->get_route( WP_REST_Server::READABLE, 'getSources', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/(?P<rowId>[\d]+)', [
			$this->get_route( WP_REST_Server::READABLE, 'loadRow', [ $this, 'permission_callback' ] ),
		] );

		$search_no_source = $this->get_search_params();
		unset( $search_no_source['source'] );
		register_rest_route( $namespace, '/source/(?P<source>[a-z\-]+)/(?P<rowId>[\d]+)', [
			'args' => array_merge(
				$search_no_source,
				[
					'columnId' => [
						'description' => 'Column within the row to update',
						'type' => 'string',
						'required' => true,
						'validate_callback' => [ $this, 'validate_replace_column' ],
					],
					'content' => [
						'description' => 'The new content',
						'type' => 'string',
						'required' => true,
					],
				]
			),
			$this->get_route( WP_REST_Server::EDITABLE, 'saveRow', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/(?P<rowId>[\d]+)/delete', [
			$this->get_route( WP_REST_Server::EDITABLE, 'deleteRow', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z\-\_]+)/(?P<rowId>[\d]+)/replace', [
			'args' => array_merge(
				$this->get_search_params(),
				[
					'rowId' => [
						'description' => 'Optional row ID',
						'type' => 'integer',
						'default' => 0,
					],
					'columnId' => [
						'description' => 'Optional column ID',
						'type' => 'string',
						'default' => null,
						'validate_callback' => [ $this, 'validate_replace_column' ],
					],
					'posId' => [
						'description' => 'Optional position ID',
						'type' => 'integer',
					],
				]
			),
			$this->get_route( WP_REST_Server::EDITABLE, 'replaceRow', [ $this, 'permission_callback' ] ),
		] );
	}

	/**
	 * Get list of all sources
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of sources, or a WP_Error
	 */
	public function getSources( WP_REST_Request $request ) {
		$sources = Source_Manager::get_all_sources();

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
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function replaceRow( WP_REST_Request $request ) {
		$params = $request->get_params();

		$flags = new Search_Flags( $params['searchFlags'] );
		$sources = Source_Manager::get( $params['source'], $flags, new Source_Flags( $params['sourceFlags'] ) );

		// Get the Search/Replace pair, with our replacePhrase as the replacement value
		$search = new Search( $params['searchPhrase'], $sources, $flags );
		$replacer = new Replace( $params['replacePhrase'], $sources, $flags );

		// Get the row
		$results = $search->get_row( $sources[0], $params['rowId'], $replacer );

		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		// Do the replacement
		$replaced = $replacer->save_and_replace( $results, isset( $params['columnId'] ) ? $params['columnId'] : null, isset( $params['posId'] ) ? intval( $params['posId'], 10 ) : null );
		if ( is_wp_error( $replaced ) ) {
			return $replaced;
		}

		// Get the row again
		$replacer = new Replace( $params['replacement'], $sources, $flags );
		$results = $search->get_row( $sources[0], $params['rowId'], $replacer );

		if ( $results instanceof \WP_Error ) {
			return $results;
		}

		return [
			'result' => $search->results_to_json( $results ),
		];
	}

	/**
	 * Save data to a row and column within a source
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function saveRow( WP_REST_Request $request ) {
		$params = $request->get_params();

		$flags = new Search_Flags( $params['searchFlags'] );
		$sources = Source_Manager::get( [ $params['source'] ], $flags, new Source_Flags( $params['sourceFlags'] ) );

		$result = $sources[0]->save( $params['rowId'], $params['columnId'], $params['content'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$search = new Search( $params['searchPhrase'], $sources, $flags );
		$replacer = new Replace( $params['replacement'], $sources, $flags );

		$row = $search->get_row( $sources[0], $params['rowId'], $replacer );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$results = $search->results_to_json( (array) $row );

		return [
			'result' => count( $results ) > 0 ? $results[0] : [],
		];
	}

	/**
	 * Load all relevant data from a source's row
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function loadRow( WP_REST_Request $request ) {
		$params = $request->get_params();

		$sources = Source_Manager::get( [ $params['source'] ], new Search_Flags(), new Source_Flags() );
		$row = $sources[0]->get_row( $params['rowId'] );

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
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function deleteRow( WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source_Manager::get( [ $params['source'] ], new Search_Flags(), new Source_Flags() );

		return $sources[0]->delete_row( $params['rowId'] );
	}

	/**
	 * Validate the replacement column
	 *
	 * @param Array|String    $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return Bool|WP_Error true or false
	 */
	public function validate_replace_column( $value, WP_REST_Request $request, $param ) {
		$params = $request->get_params();
		$sources = Source_Manager::get( [ $params['source'] ], new Search_Flags(), new Source_Flags() );
		$columns = [];

		foreach ( $sources as $source ) {
			$columns = array_merge( $columns, $source->get_columns() );
		}

		if ( in_array( $value, $columns, true ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid column detected', array( 'status' => 400 ) );
	}
}
