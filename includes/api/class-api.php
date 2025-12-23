<?php

namespace SearchRegex\Api;

class Api {
	const SEARCHREGEX_API_NAMESPACE = 'search-regex/v1';

	/**
	 * Instance variable
	 */
	private static ?Api $instance = null;

	/**
	 * Array of endpoint routes
	 *
	 * @var Route[]
	 * @phpstan-ignore property.onlyWritten
	 */
	private array $routes = [];

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
