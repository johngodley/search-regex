<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Api;
use SearchRegex\Plugin;
use WP_REST_Request;
use WP_Error;

/**
 * Settings API endpoint
 */
class Settings_Route extends Api\Route {
	/**
	 * Create API endpoints with the given namespace
	 *
	 * @param string $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route(
			$namespace, '/setting', [
				$this->get_route( \WP_REST_Server::READABLE, 'route_settings', [ $this, 'permission_callback' ] ),
				$this->get_route( \WP_REST_Server::EDITABLE, 'route_save_settings', [ $this, 'permission_callback' ] ),
			]
		);
	}

	/**
	 * Get settings
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request.
	 * @return array<string, mixed> Settings
	 */
	public function route_settings( WP_REST_Request $request ) {
		$settings = Plugin\Settings::init();

		return [
			'settings' => $settings->get_as_json(),
		];
	}

	/**
	 * Set settings
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request Request.
	 * @return array<string, mixed> Settings
	 */
	public function route_save_settings( WP_REST_Request $request ) {
		$params = $request->get_params();
		$settings = Plugin\Settings::init();

		if ( isset( $params['rest_api'] ) ) {
			$settings->set_rest_api( $params['rest_api'] );
		}

		if ( isset( $params['support'] ) ) {
			$settings->set_is_supported( $params['support'] );
		}

		// Legacy setting kept for backwards compatibility. New clients
		// should use startupMode/startupPreset instead.
		if ( isset( $params['defaultPreset'] ) ) {
			$settings->set_default_preset( $params['defaultPreset'] );
		}

		if ( isset( $params['startupMode'] ) ) {
			$settings->set_startup_mode( $params['startupMode'] );
		}

		if ( isset( $params['startupPreset'] ) ) {
			$settings->set_startup_preset( $params['startupPreset'] );
		}

		if ( isset( $params['update_notice'] ) ) {
			$settings->set_latest_version();
		}

		$settings->save();

		return $this->route_settings( $request );
	}
}
