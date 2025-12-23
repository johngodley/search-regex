<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Api;
use WP_REST_Request;
use WP_Error;

/**
 * Search API endpoint
 */
class Search_Route extends Api\Route {
	/**
	 * Return API paging args
	 *
	 * @internal
	 * @return array<string, array<string, mixed>>
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
	 * @param string $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route(
			$namespace,
			'/search',
			array_merge(
				[
					'args' => array_merge(
						$this->get_search_params(),
						$this->get_paging_params()
					),
				],
				$this->get_route( \WP_REST_Server::EDITABLE, 'route_search', [ $this, 'permission_callback' ] ),
			)
		);
	}

	/**
	 * Search for matches
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|array<string, mixed> Return an array of results, or a WP_Error
	 */
	public function route_search( WP_REST_Request $request ) {
		$params = $request->get_params();

		[ $search, $action ] = $this->get_search_replace( $params );

		$results = $search->get_search_results( $action, $params['page'], $params['perPage'], $params['limit'] );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		return $action->get_results( $results );
	}
}
