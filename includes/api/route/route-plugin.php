<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Api;

/**
 * 'Plugin' functions for Search Regex
 */
class Plugin_Route extends Api\Route {
	/**
	 * Plugin API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/plugin/test', array(
			$this->get_route( \WP_REST_Server::ALLMETHODS, 'route_test', [ $this, 'permission_callback' ] ),
		) );
	}

	/**
	 * Test the API
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return Array
	 */
	public function route_test( \WP_REST_Request $request ) {
		return array(
			'success' => true,
		);
	}
}
