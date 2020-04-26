<?php

require_once __DIR__ . '/api-search.php';
require_once __DIR__ . '/api-settings.php';
require_once __DIR__ . '/api-plugin.php';

define( 'SEARCHREGEX_API_NAMESPACE', 'search-regex/v1' );

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
class Search_Regex_Api_Route {
	protected function add_error_details( WP_Error $error, $line, $code = 400 ) {
		global $wpdb;

		$data = array(
			'status' => $code,
			'error_code' => $line,
		);

		if ( isset( $wpdb->last_error ) && $wpdb->last_error ) {
			$data['wpdb'] = $wpdb->last_error;
		}

		$error->add_data( $data );
		return $error;
	}

	public function permission_callback( WP_REST_Request $request ) {
		return Search_Regex_Capabilities::has_access( Search_Regex_Capabilities::CAP_SEARCHREGEX_SEARCH );
	}

	public function get_route( $method, $callback, $permissions = false ) {
		return [
			'methods' => $method,
			'callback' => [ $this, $callback ],
			'permission_callback' => $permissions ? $permissions : [ $this, 'permission_callback' ],
		];
	}
}

class Search_Regex_Api {
	private static $instance = null;
	private $routes = array();

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Search_Regex_Api();
		}

		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$wpdb->hide_errors();

		$this->routes[] = new Search_Regex_Api_Search( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Plugin( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Settings( SEARCHREGEX_API_NAMESPACE );
	}
}
