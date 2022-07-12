<?php

namespace SearchRegex\Plugin;

class Settings extends Plugin_Settings {
	/** @var String */
	const OPTION_NAME = 'searchregex_options';

	/** @var String */
	const API_JSON = 0;

	/** @var String */
	const API_JSON_INDEX = 1;

	/** @var String */
	const API_JSON_RELATIVE = 3;

	/**
	 * Initialize the object
	 *
	 * @return Admin
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function get_setting_name() {
		return self::OPTION_NAME;
	}

	protected function load() {
		return \apply_filters( 'searchregex_load_options', parent::load() );
	}

	protected function get_save_data( array $settings ) {
		return \apply_filters( 'searchregex_save_options', $settings );
	}

	/**
	 * Get default Search Regex options
	 *
	 * @return Array
	 */
	protected function get_defaults() {
		$defaults = [
			'support' => false,
			'rest_api' => self::API_JSON,
			'defaultPreset' => 0,
			'update_notice' => 0,
		];

		return \apply_filters( 'searchregex_default_options', $defaults );
	}

	public function set_rest_api( $rest_api ) {
		$rest_api = intval( $rest_api, 10 );

		if ( in_array( $rest_api, array( 0, 1, 2, 3, 4 ), true ) ) {
			$this->settings['rest_api'] = $rest_api;
		}
	}

	public function set_is_supported( $is_supported ) {
		$this->settings['support'] = $is_supported ? true : false;
	}

	public function set_latest_version() {
		$major_version = explode( '-', SEARCHREGEX_VERSION )[0];   // Remove any beta suffix
		$major_version = implode( '.', array_slice( explode( '.', SEARCHREGEX_VERSION ), 0, 2 ) );

		$this->settings['update_notice'] = $major_version;
	}

	public function set_default_preset( $preset_id ) {
		$this->settings['defaultPreset'] = preg_replace( '/[^A-Fa-f0-9]*/', '', $preset_id );
	}

	public function is_new_version( $major_version ) {
		return version_compare( $this->get( 'update_notice', '0' ), $major_version ) < 0;
	}

	public function is_supported() {
		return $this->get( 'support' ) ? true : false;
	}

	public function get_rest_api( $type = false ) {
		if ( $type === false ) {
			$type = $this->get( 'rest_api' );
		}

		return intval( $type, 10 );
	}

	/**
	 * Get the configured REST API
	 *
	 * @param boolean $type Type of API.
	 * @return String API URL
	 */
	public function get_rest_api_url( $type = false ) {
		$type = $this->get_rest_api( $type );
		$url = \get_rest_url();  // API_JSON

		if ( $type === self::API_JSON_INDEX ) {
			$url = \home_url( '/index.php?rest_route=/' );
		} elseif ( $type === self::API_JSON_RELATIVE ) {
			$relative = \wp_parse_url( $url, PHP_URL_PATH );

			if ( $relative ) {
				$url = $relative;
			}
		}

		return $url;
	}

	public function get_available_rest_api() {
		return [
			self::API_JSON => $this->get_rest_api_url( self::API_JSON ),
			self::API_JSON_INDEX => $this->get_rest_api_url( self::API_JSON_INDEX ),
			self::API_JSON_RELATIVE => $this->get_rest_api_url( self::API_JSON_RELATIVE ),
		];
	}

	public function get_default_preset() {
		return $this->get( 'defaultPreset' );
	}

	/**
	 * Can we save data to the database? Useful for disabling saves during debugging
	 *
	 * @return boolean
	 */
	public function can_save() {
		if ( defined( 'SEARCHREGEX_DEBUG' ) && SEARCHREGEX_DEBUG ) {
			return false;
		}

		return true;
	}
}

abstract class Plugin_Settings {
	/** @var null|Plugin_Settings */
	protected static $instance = null;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = [];

	// Don't allow this to be created directly
	public function __construct() {
		$this->settings = $this->load();

		$defaults = $this->get_defaults();

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $this->settings[ $key ] ) ) {
				$this->settings[ $key ] = $value;
			}
		}

		// Remove any expired settings
		foreach ( array_keys( $this->settings ) as $key ) {
			if ( ! isset( $defaults[ $key ] ) ) {
				unset( $this->settings[ $key ] );
			}
		}
	}

	abstract protected function get_setting_name();

	/**
	 * Get default Search Regex options
	 *
	 * @return Array
	 */
	protected function get_defaults() {
		return [];
	}

	/**
	 * Return Search Regex options
	 *
	 * @param String $name    Name of setting.
	 * @param mixed  $default Default value.
	 * @return mixed Data to return
	 */
	protected function get( $name, $default = false ) {
		if ( isset( $this->settings[ $name ] ) ) {
			return $this->settings[ $name ];
		}

		return $default;
	}

	/**
	 * Set Search Regex options. Can be passed as many options as necessary and the rest will be unchanged
	 *
	 * @param Array $settings Array of name => value.
	 * @return void
	 */
	protected function set( array $settings ) {
		$this->settings = array_merge( $this->settings, $settings );
	}

	protected function get_save_data( array $settings ) {
		return $settings;
	}

	public function save() {
		\update_option( $this->get_setting_name(), $this->get_save_data( $this->settings ) );
	}

	protected function load() {
		return \get_option( $this->get_setting_name() );
	}

	public function delete() {
		\delete_option( $this->get_setting_name() );
	}

	public function get_as_json() {
		return $this->settings;
	}
}