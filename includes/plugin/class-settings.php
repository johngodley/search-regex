<?php

namespace SearchRegex\Plugin;

class Settings extends Plugin_Settings {
	/** @var string */
	const OPTION_NAME = 'searchregex_options';

	/** @var int */
	const API_JSON = 0;

	/** @var int */
	const API_JSON_INDEX = 1;

	/** @var int */
	const API_JSON_RELATIVE = 3;

	/**
	 * Initialize the object
	 *
	 * @return Settings
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			// @phpstan-ignore new.static
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * @return string
	 */
	protected function get_setting_name() {
		return self::OPTION_NAME;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function load() {
		return \apply_filters( 'searchregex_load_options', parent::load() );
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	protected function get_save_data( array $settings ) {
		return \apply_filters( 'searchregex_save_options', $settings );
	}

	/**
	 * Get default Search Regex options
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults() {
		$defaults = [
			'support'       => false,
			'rest_api'      => self::API_JSON,
			// Legacy option kept for backwards compatibility. New installs should
			// prefer startupMode/startupPreset instead.
			'defaultPreset' => 0,
			// Startup behaviour for the UI. "advanced" preserves existing
			// behaviour until the user selects a different startup mode.
			'startupMode'   => 'advanced',
			// When startupMode is "preset" this contains the preset ID.
			'startupPreset' => '',
			'update_notice' => 0,
		];

		return \apply_filters( 'searchregex_default_options', $defaults );
	}

	/**
	 * Settings constructor.
	 *
	 * Ensures new startup options are initialised and migrates any legacy
	 * default preset configuration into the new structure.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		// If startupMode has not been initialised yet, migrate from the
		// legacy defaultPreset option so existing sites keep their behaviour.
		if ( ! isset( $this->settings['startupMode'] ) ) {
			$legacy_default = $this->get( 'defaultPreset', 0 );

			if ( ! empty( $legacy_default ) && $legacy_default !== '0' ) {
				// A preset was previously selected as the default.
				$this->settings['startupMode']   = 'preset';
				$this->settings['startupPreset'] = (string) $legacy_default;
			} else {
				// No default preset configured – previously this meant the
				// standard (advanced) UI. Keep that behaviour.
				$this->settings['startupMode']   = 'advanced';
				$this->settings['startupPreset'] = '';
			}
		}
	}

	/**
	 * @param int $rest_api
	 * @return void
	 */
	public function set_rest_api( $rest_api ) {
		$rest_api = intval( $rest_api, 10 );

		if ( in_array( $rest_api, [ 0, 1, 2, 3, 4 ], true ) ) {
			$this->settings['rest_api'] = $rest_api;
		}
	}

	/**
	 * @param bool $is_supported
	 * @return void
	 */
	public function set_is_supported( $is_supported ) {
		$this->settings['support'] = $is_supported ? true : false;
	}

	/**
	 * @return void
	 */
	public function set_latest_version() {
		$major_version = explode( '-', SEARCHREGEX_VERSION )[0];   // Remove any beta suffix
		$major_version = implode( '.', array_slice( explode( '.', SEARCHREGEX_VERSION ), 0, 2 ) );

		$this->settings['update_notice'] = $major_version;
	}

	/**
	 * @param string $preset_id
	 * @return void
	 */
	public function set_default_preset( $preset_id ) {
		// Keep legacy behaviour for callers that still use this method, but
		// avoid accepting arbitrary values.
		$cleaned = preg_replace( '/[^A-Fa-f0-9]*/', '', $preset_id );
		$this->settings['defaultPreset'] = $cleaned !== null ? $cleaned : '';
	}

	/**
	 * Set startup mode.
	 *
	 * @param string $mode Startup mode (simple, advanced, preset).
	 * @return void
	 */
	public function set_startup_mode( $mode ) {
		$mode = (string) $mode;

		if ( ! in_array( $mode, [ 'simple', 'advanced', 'preset' ], true ) ) {
			return;
		}

		$this->settings['startupMode'] = $mode;
	}

	/**
	 * Set startup preset id.
	 *
	 * @param string $preset_id Preset ID to use when startupMode is "preset".
	 * @return void
	 */
	public function set_startup_preset( $preset_id ) {
		$this->settings['startupPreset'] = (string) $preset_id;
	}

	/**
	 * Get startup mode.
	 *
	 * @return string
	 */
	public function get_startup_mode() {
		$mode = $this->get( 'startupMode', 'advanced' );

		if ( ! in_array( $mode, [ 'simple', 'advanced', 'preset' ], true ) ) {
			return 'advanced';
		}

		return $mode;
	}

	/**
	 * Get startup preset id.
	 *
	 * @return string
	 */
	public function get_startup_preset() {
		return (string) $this->get( 'startupPreset', '' );
	}

	/**
	 * @param string $major_version
	 * @return bool
	 */
	public function is_new_version( $major_version ) {
		return version_compare( $this->get( 'update_notice', '0' ), $major_version ) < 0;
	}

	/**
	 * @return bool
	 */
	public function is_supported() {
		return $this->get( 'support' ) ? true : false;
	}

	/**
	 * @param int|false $type
	 * @return int
	 */
	public function get_rest_api( $type = false ) {
		if ( $type === false ) {
			$type = $this->get( 'rest_api' );
		}

		return intval( $type, 10 );
	}

	/**
	 * Get the configured REST API
	 *
	 * @param int|false $type Type of API.
	 * @return string API URL
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

	/**
	 * @return array<int, string>
	 */
	public function get_available_rest_api() {
		return [
			self::API_JSON => $this->get_rest_api_url( self::API_JSON ),
			self::API_JSON_INDEX => $this->get_rest_api_url( self::API_JSON_INDEX ),
			self::API_JSON_RELATIVE => $this->get_rest_api_url( self::API_JSON_RELATIVE ),
		];
	}

	/**
	 * @return string|null
	 */
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
