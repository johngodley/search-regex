<?php

use SearchRegex\Search;
use SearchRegex\Replace;
use SearchRegex\Search_Flags;
use SearchRegex\Search_Source;
use SearchRegex\Source_Manager;
use SearchRegex\Source_Flags;

/**
 * @api {post} /search-regex/v1/replace Replace
 * @apiVersion 1.0.0
 * @apiName Replace
 * @apiDescription Updates the database with the replaced search phrase.
 *
 * @apiGroup Search
 *
 * @apiUse ReplaceQueryParams
 *
 * @apiUse SearchResults
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @apiDefine ReplaceQueryParams
 *
 * @apiParam (Query Parameter) {String} searchPhrase The search phrase
 * @apiParam (Query Parameter) {String} replacement The replacement phrase
 * @apiParam (Query Parameter) {Array} source The sources to search through. Currently only one source is supported
 * @apiParam (Query Parameter) {String} searchFlags[regex] Perform a regular expression search
 * @apiParam (Query Parameter) {String} searchFlags[case] Perform a case insensitive search
 * @apiParam (Query Parameter) {String} offset The current offset in the results
 * @apiParam (Query Parameter) {Integer=25,50,100,250,500} [perPage=25] The maximum number of results per page
 */

/**
 * Search API endpoint
 */
class Search_Regex_Api_Replace extends Search_Regex_Api_Route {
	/**
	 * Return API paging args
	 *
	 * @internal
	 * @return Array<String, Array>
	 */
	private function get_replace_paging_params() {
		return [
			'offset' => [
				'description' => 'The current replace offset in the results',
				'type' => 'string',
				'required' => true,
			],
			'perPage' => [
				'description' => 'The maximum number of results per page',
				'type' => 'integer',
				'default' => 25,
				'maximum' => 5000,
				'minimum' => 25,
			],
		];
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/replace', [
			'args' => array_merge(
				$this->get_search_params(),
				$this->get_replace_paging_params()
			),
			$this->get_route( WP_REST_Server::EDITABLE, 'replaceAll', [ $this, 'permission_callback' ] ),
		] );
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

		// Get all the rows to replace
		$results = $search->get_replace_results( $replacer, $params['offset'], $params['perPage'] );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		// Replace all the rows
		$replaced = $replacer->save_and_replace( $results['results'] );
		if ( is_wp_error( $replaced ) ) {
			return $replaced;
		}

		// This is 'protection' to stop things looping.
		if ( $results['next'] === $params['offset'] ) {
			$results['next'] = false;
		}

		// Return pointers to the next data
		return [
			'replaced' => $replaced,
			'progress' => [
				'next' => $results['next'],
				'rows' => $results['rows'],
			],
			'totals' => $results['totals'],
		];
	}
}
