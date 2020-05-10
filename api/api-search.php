<?php

use SearchRegex\Search;
use SearchRegex\Replace;
use SearchRegex\Search_Flags;
use SearchRegex\Search_Source;
use SearchRegex\Source_Manager;
use SearchRegex\Source_Flags;

/**
 * @api {get} /search-regex/v1/search Search
 * @apiVersion 1.0.0
 * @apiName Search
 * @apiDescription This performs the search matching of a search phrase in a search source. The search is designed to not timeout and exhaust server memory, and sometimes
 * requires the client to perform multiple requests to get a full set of results.
 *
 * A search source typically represents a WordPress database table. For example, posts, options, or comments. Some sources also represents part of
 * a database table, such as custom post types.
 *
 * Searching operates in two modes - simple (non-regular expression) or advanced (regular expression). Simple searching provides feedback on how many results
 * match the search phrase, while advanced searching requires you to search through the entire table. It should be noted that while a full result set will be returned
 * for simple searching, unless no more matches are found, advanced searching may return empty results. This indicates you need to keep on searching until the end of the source,
 * or until results are returned. This gives the client the ability to cancel a search if it is taking too long.
 *
 * Searches are returned as an array of results. Every result contains a matched phrase. Each result contains the source name (`source_name`) and row ID (`row_id`) and a number of
 * matched columns. These represent the searchable columns within a source database table.
 *
 * Each column contains a number of search contexts. A context is a group of matching phrases that are within the same section of the content. Search Regex will group matches
 * into contexts so they can be viewed easier. A maximum number of contexts will be returned, and if the search exceeds this then the information will be cropped. The `context_count`
 * value can be used to determine if cropping has occurred.
 *
 * Each context contains a number of matches along with the text for the context. Using the match information and the context it should be possible to show a visual
 * representation of the matched phrases.
 *
 * A match is an individual match of the search phrase within the row, and contains the matched phrase, the exact position within the context and the row, and any captured
 * regular expression data.
 *
 * @apiGroup Search
 *
 * @apiUse SearchQueryParams
 *
 * @apiUse SearchResults
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/replace Replace
 * @apiVersion 1.0.0
 * @apiName Replace
 * @apiDescription Updates the database with the replaced search phrase.
 *
 * @apiGroup Search
 *
 * @apiUse SearchQueryParams
 * @apiParam {String} replacePhrase The value to replace matches with
 *
 * @apiUse SearchResults
 * @apiUse 401Error
 * @apiUse 404Error
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
 * @api {get} /search-regex/v1/source/:source/:rowId Load source row
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
 * @api {post} /search-regex/v1/source/:source/:rowId Save source row
 * @apiVersion 1.0.0
 * @apiName SaveRow
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
 * @api {post} /search-regex/v1/source/:source/:rowId/replace Perform a replace on a row
 * @apiVersion 1.0.0
 * @apiName DeleteRow
 * @apiDescription Removes an entire row of data from the source
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
 * @api {post} /search-regex/v1/source/:source/:rowId/delete Delete source row
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
 * @apiDefine SearchQueryParams
 *
 * @apiParam (Query Parameter) {String} searchPhrase The search phrase
 * @apiParam (Query Parameter) {String} [replacement] The replacement phrase
 * @apiParam (Query Parameter) {Array} source The sources to search through. Currently only one source is supported
 * @apiParam (Query Parameter) {String} searchFlags[regex] Perform a regular expression search
 * @apiParam (Query Parameter) {String} searchFlags[case] Perform a case insensitive search
 * @apiParam (Query Parameter) {Integer} page The current page offset in the results
 * @apiParam (Query Parameter) {Integer=25,50,100,250,500} [perPage=25] The maximum number of results per page
 * @apiParam (Query Parameter) {String="forward","backward"} [searchDirection="forward"] The direction the search is to proceed. Only needed for regular expression searches
 */

/**
 * @apiDefine SearchResults Search results
 * Results for a Search Regex search
 *
 * @apiSuccess {Object[]} results All the search results
 * @apiSuccess {Integer} results.row_id The result row ID
 * @apiSuccess {String} results.source_type The result source type
 * @apiSuccess {String} results.source_name A displayable version of `source_type`
 * @apiSuccess {Object[]} results.columns An array of columns with matches
 * @apiSuccess {String} results.columns.column_id A column ID
 * @apiSuccess {String} results.columns.column_label A displayable name for the `column_id`
 * @apiSuccess {Object[]} results.columns.contexts An array of search contexts containing the search matches. This has a maximum size and cropping may occur (see `context_count`)
 * @apiSuccess {String} results.columns.contexts.context_id A context ID
 * @apiSuccess {String} results.columns.contexts.context The section of text from the column that contains all the matches in this context
 * @apiSuccess {Object[]} results.columns.contexts.matches The matched phrases contained within this context. This has a maximum size and cropping may occur (see `match_count`)
 * @apiSuccess {Integer} results.columns.contexts.matches.pos_id The position of the match within the row
 * @apiSuccess {Integer} results.columns.contexts.matches.context_offset The position of the match within the context
 * @apiSuccess {String} results.columns.contexts.matches.match The matched phrase
 * @apiSuccess {String} results.columns.contexts.matches.replacement The matched phrase with the replacement applied to it
 * @apiSuccess {String[]} results.columns.contexts.matches.captures If a regular expression search then this will contain any captured groups
 * @apiSuccess {Integer} results.columns.contexts.match_count The total number of matched phrases, including any that have been cropped.
 * @apiSuccess {Integer} results.columns.context_count The total possible number of contexts, including any from `contexts` that are cropped
 * @apiSuccess {String} results.columns.match_count The number of matches
 * @apiSuccess {String} results.columns.replacement The search phrase
 * @apiSuccess {Object[]} results.actions An array of actions that can be performed on this result
 * @apiSuccess {String} results.title A title for the result
 * @apiSuccess {Object[]} totals The totals for this search
 * @apiSuccess {Integer} totals.current The current search offset
 * @apiSuccess {Integer} totals.rows The total number of rows for the source, including non-matches
 * @apiSuccess {Integer} totals.matched_rows The number of matched rows if known, or `-1` if a regular expression match and unknown
 * @apiSuccess {Integer} totals.matched_phrases The number of matched phraes if known, or `-1` if a regular expression match and unknown
 * @apiSuccess {Object[]} progress The current search progress, and the previous and next set of results
 * @apiSuccess {Integer} progress.current The current search offset
 * @apiSuccess {Integer} progress.rows The number of rows contained within this result set
 * @apiSuccess {Integer} progress.previous The offset for the previous set of results
 * @apiSuccess {Integer} progress.next The offset for the next set of results
 */

/**
 * @apiDefine SearchResult Search results
 * Results for a Search Regex search
 *
 * @apiSuccess {Integer} result.row_id The result row ID
 * @apiSuccess {String} result.source_type The result source type
 * @apiSuccess {String} result.source_name A displayable version of `source_type`
 * @apiSuccess {Object[]} result.columns An array of columns with matches
 * @apiSuccess {String} result.columns.column_id A column ID
 * @apiSuccess {String} result.columns.column_label A displayable name for the `column_id`
 * @apiSuccess {String} result.columns.match_count The total number of matches across all contexts in this column
 * @apiSuccess {String} result.columns.replacement The column with all matches replaced
 * @apiSuccess {Integer} result.columns.context_count The total possible number of contexts, including any from `contexts` that are cropped
 * @apiSuccess {Object[]} result.columns.contexts An array of search contexts containing the search matches. This has a maximum size and cropping may occur (see `context_count`)
 * @apiSuccess {String} result.columns.contexts.context_id A context ID
 * @apiSuccess {String} result.columns.contexts.context The section of text from the column that contains all the matches in this context
 * @apiSuccess {Integer} result.columns.contexts.match_count The total number of matched phrases, including any that have been cropped.
 * @apiSuccess {Object[]} result.columns.contexts.matches The matched phrases contained within this context. This has a maximum size and cropping may occur (see `match_count`)
 * @apiSuccess {Integer} result.columns.contexts.matches.pos_id The position of the match within the row
 * @apiSuccess {Integer} result.columns.contexts.matches.context_offset The position of the match within the context
 * @apiSuccess {String} result.columns.contexts.matches.match The matched phrase
 * @apiSuccess {String} result.columns.contexts.matches.replacement The matched phrase with the replacement applied to it
 * @apiSuccess {String[]} result.columns.contexts.matches.captures If a regular expression search then this will contain any captured groups
 * @apiSuccess {Object[]} result.actions An array of actions that can be performed on this result
 * @apiSuccess {String} result.title A title for the result
 */

/**
 * Search API endpoint
 */
class Search_Regex_Api_Search extends Search_Regex_Api_Route {
	/**
	 * Return API search args
	 *
	 * @internal
	 * @return Array<String, Array>
	 */
	private function get_search_params() {
		return [
			'searchPhrase' => [
				'description' => 'The search phrase',
				'type' => 'string',
				'validate_callback' => [ $this, 'validate_search' ],
				'required' => true,
			],
			'replacement' => [
				'description' => 'The replacement phrase',
				'type' => 'string',
				'default' => '',
			],
			'source' => [
				'description' => 'The sources to search through. Currently only one source is supported',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'validate_callback' => [ $this, 'validate_source' ],
				'required' => true,
			],
			'sourceFlags' => [
				'description' => 'Source flags',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_source_flags' ],
			],
			'searchFlags' => [
				'description' => 'Search flags',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_search_flags' ],
			],
		];
	}

	/**
	 * Return API paging args
	 *
	 * @internal
	 * @return Array<String, Array>
	 */
	private function get_paging_params() {
		return [
			'page' => [
				'description' => 'The current page offset in the results',
				'type' => 'integer',
				'default' => 0,
				'minimum' => 0,
			],
			'perPage' => [
				'description' => 'The maximum number of results per page',
				'type' => 'integer',
				'default' => 25,
				'maximum' => 500,
				'minimum' => 25,
			],
			'searchDirection' => [
				'description' => 'The direction the search is to proceed. Only needed for regular expression searches',
				'type' => 'string',
				'default' => 'forward',
				'enum' => [
					'forward',
					'backward',
				],
			],
			'limit' => [
				'description' => 'Maximum number of results to return',
				'type' => 'integer',
				'default' => 0,
			],
		];
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/search', [
			'args' => array_merge(
				$this->get_search_params(),
				$this->get_paging_params()
			),
			$this->get_route( WP_REST_Server::READABLE, 'search', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/replace', [
			'args' => array_merge(
				$this->get_search_params(),
				$this->get_paging_params(),
				[
					'replacePhrase' => [
						'description' => 'The value to replace matches with',
						'type' => 'string',
						'required' => true,
					],
				]
			),
			$this->get_route( WP_REST_Server::EDITABLE, 'replaceAll', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z]+)/(?P<rowId>[\d]+)', [
			$this->get_route( WP_REST_Server::READABLE, 'loadRow', [ $this, 'permission_callback' ] ),
		] );

		$search_no_source = $this->get_search_params();
		unset( $search_no_source['source'] );
		register_rest_route( $namespace, '/source/(?P<source>[a-z]+)/(?P<rowId>[\d]+)', [
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

		register_rest_route( $namespace, '/source/(?P<source>[a-z]+)/(?P<rowId>[\d]+)/delete', [
			$this->get_route( WP_REST_Server::EDITABLE, 'deleteRow', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/source/(?P<source>[a-z]+)/(?P<rowId>[\d]+)/replace', [
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
	 * Helper to return a search and replace object
	 *
	 * @internal
	 * @param Array  $params Array of params.
	 * @param String $replacement Replacement value.
	 * @return Array Search and Replace objects
	 */
	private function get_search_replace( $params, $replacement ) {
		// Get basics
		$flags = new Search_Flags( $params['searchFlags'] );
		$sources = Source_Manager::get( $params['source'], $flags, new Source_Flags( $params['sourceFlags'] ) );

		// Create a search and replacer
		$search = new Search( $params['searchPhrase'], $sources, $flags );
		$replacer = new Replace( $replacement, $sources, $flags );

		return [ $search, $replacer ];
	}

	/**
	 * Search for matches
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();

		list( $search, $replacer ) = $this->get_search_replace( $params, $params['replacement'] );

		$results = $search->get_results( $replacer, $params['page'], $params['perPage'], $params['limit'] );
		if ( ! is_wp_error( $results ) ) {
			$results['results'] = $search->results_to_json( $results['results'] );
		}

		return $results;
	}

	/**
	 * Perform a replacement on all rows
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function replaceAll( WP_REST_Request $request ) {
		$params = $request->get_params();

		list( $search, $replacer ) = $this->get_search_replace( $params, $params['replacement'] );
		$results = $search->get_results( $replacer, $params['page'], $params['perPage'] );

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$results = $replacer->save_and_replace( $results['results'] );
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$search_results = $this->search( $request );
		if ( $search_results instanceof \WP_Error ) {
			return $search_results;
		}

		// Do the replacement
		return [
			'results' => $results,
			'progress' => $search_results['progress'],
			'totals' => $search_results['totals'],
		];
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
	 * Validate that the search parameter is correct
	 *
	 * @param String          $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return Bool true or false
	 */
	public function validate_search( $value, WP_REST_Request $request, $param ) {
		$value = trim( $value );

		return strlen( $value ) > 0;
	}

	/**
	 * Validate that the column is correct for the given source
	 *
	 * @param String|null     $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return WP_Error|Bool true or false
	 */
	public function validate_replace_column( $value, WP_REST_Request $request, $param ) {
		if ( $value === null ) {
			return true;
		}

		$handlers = Source_Manager::get( Source_Manager::get_all_source_names(), new Search_Flags(), new Source_Flags() );

		foreach ( $handlers as $handler ) {
			if ( in_array( $value, $handler->get_columns(), true ) ) {
				return true;
			}
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid column detected', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the search flags are correct
	 *
	 * @param Array|String    $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return WP_Error|Bool true or false
	 */
	public function validate_search_flags( $value, WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			$source = new Search_Flags( $value );

			if ( count( $source->get_flags() ) === count( $value ) ) {
				return true;
			}
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid search flag detected', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the source flags are correct
	 *
	 * @param Array|String    $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return Bool|WP_Error true or false
	 */
	public function validate_source_flags( $value, WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			$params = $request->get_params();
			$sources = Source_Manager::get( is_array( $params['source'] ) ? $params['source'] : [ $params['source'] ], new Search_Flags(), new Source_Flags( $value ) );

			// Get the sanitized flags from all the sources
			$allowed = [];
			foreach ( $sources as $source ) {
				$allowed = array_merge( $allowed, array_keys( $source->get_supported_flags() ) );
			}

			// Make it unique, as some sources can use the same flag
			$allowed = array_values( array_unique( $allowed ) );

			// Filter the value by this allowed list
			$filtered_value = array_filter( $value, function( $item ) use ( $allowed ) {
				return in_array( $item, $allowed, true );
			} );

			// Any flags missing?
			if ( count( $filtered_value ) === count( $value ) ) {
				return true;
			}
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid source flag detected', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the source is valid
	 *
	 * @param Array|String    $value The value to validate.
	 * @param WP_REST_Request $request The request.
	 * @param Array           $param The array of parameters.
	 * @return Bool|WP_Error true or false
	 */
	public function validate_source( $value, WP_REST_Request $request, $param ) {
		$allowed = Source_Manager::get_all_source_names();

		add_filter( 'wp_revisions_to_keep', [ $this, 'disable_post_revisions' ] );

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$valid = array_filter( $value, function( $item ) use ( $allowed ) {
			return array_search( $item, $allowed, true ) !== false;
		} );

		if ( count( $valid ) === count( $value ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid source detected', array( 'status' => 400 ) );
	}

	/**
	 * Used to disable post revisions when updating a post
	 *
	 * @internal
	 * @return Int
	 */
	public function disable_post_revisions() {
		return 0;
	}
}
