<?php

namespace SearchRegex\Plugin;

abstract class Plugin_Settings {
	/** @var static|null */
	protected static $instance = null;

	/**
	 * Settings
	 *
	 * @var array<string, mixed>
	 */
	protected array $settings = [];

	// Don't allow this to be created directly
	/**
	 * @return void
	 */
	public function __construct() {
		$this->settings = $this->load();

		$defaults = $this->get_defaults();

		foreach ( $defaults as $key => $value ) {
			$this->settings[ $key ] ??= $value;
		}

		// Remove any expired settings
		foreach ( array_keys( $this->settings ) as $key ) {
			if ( ! isset( $defaults[ $key ] ) ) {
				unset( $this->settings[ $key ] );
			}
		}
	}

	/**
	 * @return string
	 */
	abstract protected function get_setting_name();

	/**
	 * Get default Search Regex options
	 *
	 * @return array<string, mixed>
	 */
	protected function get_defaults() {
		return [];
	}

	/**
	 * Return Search Regex options
	 *
	 * @param string $name Name of setting.
	 * @param mixed $default_value Default value.
	 * @return mixed Data to return
	 */
	protected function get( $name, $default_value = false ) {
		return $this->settings[ $name ] ?? $default_value;
	}

	/**
	 * Set Search Regex options. Can be passed as many options as necessary and the rest will be unchanged
	 *
	 * @param array<string, mixed> $settings Array of name => value.
	 * @return void
	 */
	protected function set( array $settings ) {
		// @phpstan-ignore-next-line
		$this->settings = array_merge( $this->settings, $settings );
	}

	/**
	 * @param array<string, mixed> $settings Settings array.
	 * @return array<string, mixed>
	 */
	protected function get_save_data( array $settings ) {
		return $settings;
	}

	/**
	 * @return void
	 */
	public function save() {
		\update_option( $this->get_setting_name(), $this->get_save_data( $this->settings ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function load() {
		return \get_option( $this->get_setting_name(), [] );
	}

	/**
	 * @return bool
	 */
	public function delete() {
		return \delete_option( $this->get_setting_name() );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_as_json() {
		return $this->settings;
	}
}
