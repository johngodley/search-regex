<?php

use SearchRegex\Search;
use SearchRegex\Saved_Search;

/**
 * @api {post} /search-regex/v1/search Search
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
 * Search API endpoint
 */
class Search_Regex_Api_Search extends Search_Regex_Api_Route {
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
				'maximum' => 5000,
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
			$this->get_route( WP_REST_Server::EDITABLE, 'route_search', [ $this, 'permission_callback' ] ),
		] );
	}

	/**
	 * Search for matches
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_Error|array Return an array of results, or a WP_Error
	 */
	public function route_search( WP_REST_Request $request ) {
		$params = $request->get_params();

		list( $search, $replacer ) = $this->get_search_replace( $params, $params['replacement'] );

		$results = $search->get_search_results( $replacer, $params['page'], $params['perPage'], $params['limit'] );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		$results['results'] = $search->results_to_json( $results['results'] );
		return $results;
	}
}
