<?php

namespace SearchRegex\Api;

require_once __DIR__ . '/class-route.php';
require_once __DIR__ . '/route/route-search.php';
require_once __DIR__ . '/route/route-source.php';
require_once __DIR__ . '/route/route-settings.php';
require_once __DIR__ . '/route/route-plugin.php';
require_once __DIR__ . '/route/route-preset.php';

class Api {
	const SEARCHREGEX_API_NAMESPACE = 'search-regex/v1';

	/**
	 * Instance variable
	 *
	 * @var Api|null
	 **/
	private static $instance = null;

	/**
	 * Array of endpoint routes
	 *
	 * @var Array
	 **/
	private $routes = array();

	/**
	 * Create API
	 *
	 * @return Api
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Api();
		}

		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$wpdb->hide_errors();

		$this->routes[] = new Route\Search_Route( self::SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Route\Source_Route( self::SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Route\Plugin_Route( self::SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Route\Settings_Route( self::SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Route\Preset_Route( self::SEARCHREGEX_API_NAMESPACE );
	}
}
