<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Api;
use WP_REST_Request;

/**
 * 'Plugin' functions for Search Regex
 */
class Plugin_Route extends Api\Route {
	/**
	 * Plugin API endpoint constructor
	 *
	 * @param string $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route(
			$namespace, '/plugin/test', [
				$this->get_route( \WP_REST_Server::ALLMETHODS, 'route_test', [ $this, 'permission_callback' ] ),
			]
		);
	}

	/**
	 * Test the API
	 *
	 * @param WP_REST_Request<array<string, mixed>> $_request Request.
	 * @return array{success: bool}
	 */
	public function route_test( WP_REST_Request $_request ) {
		return [
			'success' => true,
		];
	}
}
