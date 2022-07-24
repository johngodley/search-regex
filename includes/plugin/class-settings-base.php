<?php

namespace SearchRegex\Plugin;

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
