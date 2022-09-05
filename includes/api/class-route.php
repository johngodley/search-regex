<?php

namespace SearchRegex\Api;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Plugin;

/**
 * @apiDefine SearchQueryParams Search parameters
 *
 * The `replacement` and `searchFlags` are only used if a global `searchPhrase` is provided. This is a backwards-compatible global search and replace
 *
 * @apiParam (Search Query) {Integer} [page=0] Page to search
 * @apiParam (Search Query) {Integer} [perPage=25] Number of results per page
 * @apiParam (Search Query) {String="forward","backward"} [searchDirection=forward] Direction to search. Only needed for regular expression searches
 * @apiParam (Search Query) {String="nothing","modify","replace","delete","export"} [action="nothing"] Action to perform on the search results, or do nothing
 * @apiParam (Search Query) {Object} [actionOption] Options for the action
 * @apiParam (Search Query) {String} [replacement=""] Global replacement value
 * @apiParam (Search Query) {String[]} [searchFlags="case"] Flags for the global replacement
 * @apiParam (Search Query) {String[]} source The search sources to perform the search over
 * @apiParam (Search Query) {String[]} [view] Any additional columns to return data for. Specified in the format `source__column`
 * @apiParam (Search Filter) {Object[]} [filters] Additional column filters. Each `filter` is `AND`ed together, so all must match for a row to be considered a match.
 * @apiParam (Search Filter) {Object[]} [filters.items] Filters for columns within the source. These are `OR`ed together.
 * @apiParam (Search Filter) {String} [filters.items.column] Column name. The column determines what other values should be present in the filter
 * @apiParam (Search Filter) {String} [filters.items.logic] Logic for the filter. Date and integer supports 'equals', 'notequals', 'greater', 'less', 'range'. String supports 'equals', 'notequals', 'contains', 'notcontains', 'begins', 'ends'. Member supports `include` and `exclude`
 * @apiParam (Search Filter) {Integer|Date} [filters.items.startValue] Lower value for a range, or single value for `integer` or `date`
 * @apiParam (Search Filter) {Integer|Date} [filters.items.endValue] Upper value for a range
 * @apiParam (Search Filter) {String} [filters.items.key] Key for a keyvalue pair
 * @apiParam (Search Filter) {String} [filters.items.value] Value for a keyvalue pair
 * @apiParam (Search Filter) {String} [filters.items.keyLogic] Logic for the filter (see `string`)
 * @apiParam (Search Filter) {String} [filters.items.valueLogic] Logic for the filter (see `string`)
 * @apiParam (Search Filter) {String} [filters.items.value] Single value for a string
 * @apiParam (Search Filter) {String[]} [filters.items.values] Values for `member`
 * @apiParam (Search Filter) {String} [filters.items.flags] Search flags for `string` or `keyvalue`
 */

/**
 * @apiDefine SearchResults Search results
 * Results for a Search Regex search
 *
 * @apiSuccess {Object[]} results All the search results
 * @apiSuccess {Integer} results.row_id The result row ID
 * @apiSuccess {String} results.source_type The result source type
 * @apiSuccess {String} results.source_name A displayable version of `source_type`
 * @apiSuccess {Object[]} results.actions An array of actions that can be performed on this result
 * @apiSuccess {String} results.title A title for the result
 * @apiSuccess {Object[]} results.columns An array of columns with matches
 * @apiSuccess {String} results.columns.column_id A column ID
 * @apiSuccess {String} results.columns.column_label A displayable name for the `column_id`
 * @apiSuccess {Integer} results.columns.context_count The total possible number of contexts, including any from `contexts` that are cropped
 * @apiSuccess {String} results.columns.match_count The number of matches
 * @apiSuccess {Object[]} results.columns.contexts An array of search contexts containing the search matches. This has a maximum size and cropping may occur (see `context_count`)
 * @apiSuccess {String} results.columns.contexts.context_id A context ID
 * @apiSuccess {String="value","add","delete","empty","keyvalue","replace","string"} results.columns.contexts.type The context type. This determines what other data is available
 * @apiSuccess {String} results.columns.contexts.context The section of text from the column that contains all the matches in this context
 * @apiSuccess {Object[]} results.columns.contexts.matches The matched phrases contained within this context. This has a maximum size and cropping may occur (see `match_count`)
 * @apiSuccess {Integer} results.columns.contexts.matches.pos_id The position of the match within the row
 * @apiSuccess {Integer} results.columns.contexts.matches.context_offset The position of the match within the context
 * @apiSuccess {String} results.columns.contexts.matches.match The matched phrase
 * @apiSuccess {String} results.columns.contexts.matches.replacement The matched phrase with the replacement applied to it
 * @apiSuccess {String[]} results.columns.contexts.matches.captures If a regular expression search then this will contain any captured groups
 * @apiSuccess {Integer} results.columns.contexts.match_count The total number of matched phrases, including any that have been cropped.
 * @apiSuccess {Object[]} totals The totals for this search
 * @apiSuccess {Integer} totals.current The current search offset
 * @apiSuccess {Integer} totals.rows The total number of rows for the source, including non-matches
 * @apiSuccess {Integer} totals.matched_rows The number of matched rows if known, or 0 if a regular expression match and unknown
 * @apiSuccess {Object[]} progress The current search progress, and the previous and next set of results
 * @apiSuccess {Integer} progress.current The current search offset
 * @apiSuccess {Integer} progress.rows The number of rows contained within this result set
 * @apiSuccess {Integer} progress.previous The offset for the previous set of results
 * @apiSuccess {Integer} progress.next The offset for the next set of results
 */

/**
 * @apiDefine SearchResult Single search result
 *
 * @apiSuccess {Object} result A result
 * @apiSuccess {Integer} result.row_id Row ID
 * @apiSuccess {String} result.source_type The source type (i.e. 'posts')
 * @apiSuccess {String} result.source_name Source name suitable for display (i.e. 'Posts')
 * @apiSuccess {Object[]} results.columns An array of columns with matches
 * @apiSuccess {String} results.columns.column_id A column ID
 * @apiSuccess {String} results.columns.column_label A displayable name for the `column_id`
 * @apiSuccess {Integer} results.columns.context_count The total possible number of contexts, including any from `contexts` that are cropped
 * @apiSuccess {String} results.columns.match_count The number of matches
 * @apiSuccess {Object[]} results.columns.contexts An array of search contexts containing the search matches. This has a maximum size and cropping may occur (see `context_count`)
 * @apiSuccess {String} results.columns.contexts.context_id A context ID
 * @apiSuccess {String="value","add","delete","empty","keyvalue","replace","string"} results.columns.contexts.type The context type. This determines what other data is available
 * @apiSuccess {String} results.columns.contexts.context The section of text from the column that contains all the matches in this context
 * @apiSuccess {Object[]} results.columns.contexts.matches The matched phrases contained within this context. This has a maximum size and cropping may occur (see `match_count`)
 * @apiSuccess {Integer} results.columns.contexts.matches.pos_id The position of the match within the row
 * @apiSuccess {Integer} results.columns.contexts.matches.context_offset The position of the match within the context
 * @apiSuccess {String} results.columns.contexts.matches.match The matched phrase
 * @apiSuccess {String} results.columns.contexts.matches.replacement The matched phrase with the replacement applied to it
 * @apiSuccess {String[]} results.columns.contexts.matches.captures If a regular expression search then this will contain any captured groups
 * @apiSuccess {Integer} results.columns.contexts.match_count The total number of matched phrases, including any that have been cropped.
 * @apiSuccess {Object[]} result.actions
 * @apiSuccess {String} result.title - Title for this result
 * @apiSuccess {Integer} result.match_count - Number of matches in this result
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
class Route {
	/**
	 * Checks a capability
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return Bool
	 */
	public function permission_callback( \WP_REST_Request $request ) {
		/** @psalm-suppress UndefinedClass */
		return Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH );
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
			'searchFlags' => [
				'description' => 'Search flags',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_search_flags' ],
			],
			'filters' => [
				'description' => 'Search filters',
				'type' => 'array',
				'validate_callback' => [ $this, 'validate_filters' ],
				'default' => [],
			],
			'view' => [
				'description' => 'Additional columns to view',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_view' ],
			],
			'action' => [
				'description' => 'Action to perform on the search',
				'type' => 'string',
				'default' => 'nothing',
				'validate_callback' => [ $this, 'validate_action' ],
			],
			'actionOption' => [
				'description' => 'Options for the action',
				'type' => 'object',
				'default' => [],
			],
		];
	}

	/**
	 * Helper to return a search and replace object
	 *
	 * @param Array $params Array of params.
	 * @return Array{Search\Search,Action\Action} Search and Replace objects
	 */
	protected function get_search_replace( $params ) {
		$schema = new Schema\Schema( Source\Manager::get_schema( $params['source'] ) );
		$filters = isset( $params['filters'] ) ? Filter\Filter::create( $params['filters'], $schema ) : [];

		// Create the actions for the search
		$action = Action\Action::create( isset( $params['action'] ) ? $params['action'] : '', Action\Action::get_options( $params ), $schema );

		// Convert global search to filters
		if ( isset( $params['searchPhrase'] ) && $params['searchPhrase'] ) {
			$filters[] = new Filter\Global_Filter( $params['searchPhrase'], $params['searchFlags'] );
		}

		// Are we doing the action for real or just a dry run?
		if ( isset( $params['save'] ) && $params['save'] ) {
			$action->set_save_mode( true );
		}

		// Add any view columns
		$columns = $action->get_view_columns();
		if ( isset( $params['view'] ) ) {
			$columns = array_unique( array_merge( $columns, $params['view'] ) );
		}

		$filters = Filter\Filter::get_missing_column_filters( $schema, $filters, $columns );

		// Get sources
		$sources = Source\Manager::get( $params['source'], $filters );

		// Create the search, using the filters
		$search = new Search\Search( $sources );

		return [ $search, $action ];
	}

	/**
	 * Validate that the search flags are correct
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return \WP_Error|Bool true or false
	 */
	public function validate_search_flags( $value, \WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			$source = new Search\Flags( $value );

			if ( count( $source->get_flags() ) === count( $value ) ) {
				return true;
			}
		}

		return new \WP_Error( 'rest_invalid_param', 'Invalid search flag detected', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the view columns are valid
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return \WP_Error|Bool true or false
	 */
	public function validate_view( $value, \WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $view ) {
				$parts = explode( '__', $view );
				if ( count( $parts ) !== 2 ) {
					return new \WP_Error( 'rest_invalid_param', 'Invalid view parameter', array( 'status' => 400 ) );
				}
			}

			return true;
		}

		return new \WP_Error( 'rest_invalid_param', 'Invalid view parameter', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the view columns are valid
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return \WP_Error|Bool true or false
	 */
	public function validate_action( $value, \WP_REST_Request $request, $param ) {
		if ( in_array( $value, [ 'modify', 'replace', 'delete', 'export', 'nothing', 'action', '' ], true ) ) {
			return true;
		}

		return new \WP_Error( 'rest_invalid_param', 'Invalid view parameter', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the source is valid
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return Bool|\WP_Error true or false
	 */
	public function validate_source( $value, \WP_REST_Request $request, $param ) {
		$allowed = Source\Manager::get_all_source_names();

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

		return new \WP_Error( 'rest_invalid_param', 'Invalid source detected', array( 'status' => 400 ) );
	}

	/**
	 * Validate supplied filters
	 *
	 * @param string|array     $value Value.
	 * @param \WP_REST_Request $request Request.
	 * @param array            $param Params.
	 * @return boolean
	 */
	public function validate_filters( $value, \WP_REST_Request $request, $param ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $filter ) {
			if ( ! is_array( $filter ) ) {
				return false;
			}

			// Check type and items are present
			if ( ! isset( $filter['type'] ) || ! isset( $filter['items'] ) ) {
				return false;
			}
		}

		return true;
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

	/**
	 * Does the array contain the supplied keys?
	 *
	 * @param array        $keys Keys.
	 * @param array|string $item Item.
	 * @return true|\WP_Error
	 */
	protected function contains_keys( array $keys, $item ) {
		if ( ! is_array( $item ) ) {
			return new \WP_Error( 'rest_invalid_param', 'Item is not an array', [ 'status' => 400 ] );
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $item[ $key ] ) ) {
				return new \WP_Error( 'rest_invalid_param', 'Item does not contain key ' . $key, [ 'status' => 400 ] );
			}
		}

		return true;
	}
}
