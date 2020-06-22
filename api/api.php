<?php

require_once __DIR__ . '/api-base.php';
require_once __DIR__ . '/api-search.php';
require_once __DIR__ . '/api-replace.php';
require_once __DIR__ . '/api-source.php';
require_once __DIR__ . '/api-settings.php';
require_once __DIR__ . '/api-plugin.php';
require_once __DIR__ . '/api-preset.php';

define( 'SEARCHREGEX_API_NAMESPACE', 'search-regex/v1' );

class Search_Regex_Api {
	/**
	 * Instance variable
	 *
	 * @var Search_Regex_Api|null
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
	 * @return Search_Regex_Api
	 */
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
		$this->routes[] = new Search_Regex_Api_Replace( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Source( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Plugin( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Settings( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Preset( SEARCHREGEX_API_NAMESPACE );
	}
}
