<?php

require_once __DIR__ . '/api-base.php';
require_once __DIR__ . '/api-search.php';
require_once __DIR__ . '/api-settings.php';
require_once __DIR__ . '/api-plugin.php';

define( 'SEARCHREGEX_API_NAMESPACE', 'search-regex/v1' );

class Search_Regex_Api {
	/** @var Search_Regex_Api|null */
	private static $instance = null;
	/** @var Array */
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
		$this->routes[] = new Search_Regex_Api_Plugin( SEARCHREGEX_API_NAMESPACE );
		$this->routes[] = new Search_Regex_Api_Settings( SEARCHREGEX_API_NAMESPACE );
	}
}
