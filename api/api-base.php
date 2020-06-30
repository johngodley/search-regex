<?php

use SearchRegex\Search_Flags;
use SearchRegex\Source_Manager;
use SearchRegex\Source_Flags;
use SearchRegex\Search;
use SearchRegex\Replace;

/**
 * @apiDefine SearchQueryParams Search query
 * Query parameters for a search
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
 * @apiDefine 401Error
 *
 * @apiError (Error 401) rest_forbidden You are not authorized to access this API endpoint
 * @apiErrorExample {json} 401 Error Response:
 *     HTTP/1.1 401 Bad Request
 *     {
 *       "code": "rest_forbidden",
 *       "message": "Sorry, you are not allowed to do that."
 *     }
 */

/**
 * @apiDefine 404Error
 *
 * @apiError (Error 404) rest_no_route Endpoint not found
 * @apiErrorExample {json} 404 Error Response:
 *     HTTP/1.1 404 Not Found
 *     {
 *       "code": "rest_no_route",
 *       "message": "No route was found matching the URL and request method"
 *     }
 */

/**
 * @apiDefine 400Error
 *
 * @apiError rest_forbidden You are not authorized to access this API endpoint
 * @apiErrorExample {json} 400 Error Response:
 *     HTTP/1.1 400 Bad Request
 *     {
 *       "error": "invalid",
 *       "message": "Invalid request"
 *     }
 */

/**
 * @apiDefine 400MissingError
 * @apiError (Error 400) rest_missing_callback_param Some required parameters are not present or not in the correct format
 * @apiErrorExample {json} 400 Error Response:
 *     HTTP/1.1 400 Bad Request
 *     {
 *       "code": "rest_missing_callback_param",
 *       "message": "Missing parameter(s): PARAM"
 *     }
 */

/**
 * Base class for Search Regex API endpoints
 */
class Search_Regex_Api_Route {
	/**
	 * Checks a capability
	 *
	 * @param WP_REST_Request $request Request.
	 * @return Bool
	 */
	public function permission_callback( WP_REST_Request $request ) {
		return Search_Regex_Capabilities::has_access( Search_Regex_Capabilities::CAP_SEARCHREGEX_SEARCH );
	}

	/**
	 * Get route details
	 *
	 * @param String        $method Method name.
	 * @param String        $callback Function name.
	 * @param callable|Bool $permissions Permissions callback.
	 * @return Array{methods: string, callback: callable, permission_callback: callable|bool} Route detials
	 */
	public function get_route( $method, $callback, $permissions = false ) {
		return [
			'methods' => $method,
			'callback' => [ $this, $callback ],
			'permission_callback' => $permissions ? $permissions : [ $this, 'permission_callback' ],
		];
	}

	/**
	 * Return API search args
	 *
	 * @return Array<String, Array>
	 */
	protected function get_search_params() {
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
	 * Helper to return a search and replace object
	 *
	 * @param Array  $params Array of params.
	 * @param String $replacement Replacement value.
	 * @return Array{Search,Replace} Search and Replace objects
	 */
	protected function get_search_replace( $params, $replacement ) {
		// Get basics
		$flags = new Search_Flags( $params['searchFlags'] );
		$sources = Source_Manager::get( $params['source'], $flags, new Source_Flags( $params['sourceFlags'] ) );

		// Create a search and replacer
		$search = new Search( $params['searchPhrase'], $sources, $flags );
		$replacer = new Replace( $replacement, $sources, $flags );

		return [ $search, $replacer ];
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
			$search = isset( $params['search'] ) ? $params['search'] : $params;
			$sources = Source_Manager::get( is_array( $search['source'] ) ? $search['source'] : [ $search['source'] ], new Search_Flags(), new Source_Flags( $value ) );

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
		add_filter( 'wp_insert_post_data', [ $this, 'wp_insert_post_data' ] );

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
	 * Used to disable post revisions when updating a post
	 *
	 * @internal
	 * @return Int
	 */
	public function disable_post_revisions() {
		return 0;
	}

	/**
	 * Stops wp_update_post from changing the post_modified date
	 *
	 * @internal
	 * @param Array $data Array of post data.
	 * @return Array
	 */
	public function wp_insert_post_data( $data ) {
		if ( isset( $data['post_modified'] ) ) {
			unset( $data['post_modified'] );
			unset( $data['post_modified_gmt'] );
		}

		return $data;
	}
}
