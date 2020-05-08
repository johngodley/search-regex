<?php

/**
 * 'Plugin' functions for Search Regex
 */
class Search_Regex_Api_Plugin extends Search_Regex_Api_Route {
	/**
	 * Plugin API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/plugin/test', array(
			$this->get_route( WP_REST_Server::ALLMETHODS, 'route_test', [ $this, 'permission_callback' ] ),
		) );
	}

	/**
	 * Test the API
	 *
	 * @param WP_REST_Request $request Request.
	 * @return Array
	 */
	public function route_test( WP_REST_Request $request ) {
		return array(
			'success' => true,
		);
	}
}
