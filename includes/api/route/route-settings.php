<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Api;
use SearchRegex\Plugin;

/**
 * @api {get} /search-regex/v1/setting Get settings
 * @apiVersion 1.0.0
 * @apiName GetSettings
 * @apiDescription Get all settings for Search Regex. This includes user-configurable settings, as well as necessary WordPress settings.
 * @apiGroup Settings
 *
 * @apiUse SettingItem
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/setting Update settings
 * @apiVersion 1.0.0
 * @apiName UpdateSettings
 * @apiDescription Update Search Regex settings. Note you can do partial updates, and only the values specified will be changed.
 * @apiGroup Settings
 *
 * @apiParam {Object} settings An object containing all the settings to update
 * @apiParamExample {json} settings:
 *     {
 *       "expire_redirect": 14,
 *       "https": false
 *     }
 *
 * @apiUse SettingItem
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @apiDefine SettingItem Settings
 * Search Regex settings
 *
 * @apiSuccess {Object[]} settings An object containing all settings
 * @apiSuccess {String} settings.support
 * @apiSuccess {String} settings.rest_api
 *
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "settings": {
 *         "support": false,
 *       }
 *     }
 */

/**
 * Settings API endpoint
 */
class Settings_Route extends Api\Route {
	/**
	 * Create API endpoints with the given namespace
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/setting', array(
			$this->get_route( \WP_REST_Server::READABLE, 'route_settings', [ $this, 'permission_callback' ] ),
			$this->get_route( \WP_REST_Server::EDITABLE, 'route_save_settings', [ $this, 'permission_callback' ] ),
		) );
	}

	/**
	 * Get settings
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return Array Settings
	 */
	public function route_settings( \WP_REST_Request $request ) {
		$settings = Plugin\Settings::init();

		return [
			'settings' => $settings->get_as_json(),
		];
	}

	/**
	 * Set settings
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return Array Settings
	 */
	public function route_save_settings( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$settings = Plugin\Settings::init();

		if ( isset( $params['rest_api'] ) ) {
			$settings->set_rest_api( $params['rest_api'] );
		}

		if ( isset( $params['support'] ) ) {
			$settings->set_is_supported( $params['support'] );
		}

		if ( isset( $params['defaultPreset'] ) ) {
			$settings->set_default_preset( $params['defaultPreset'] );
		}

		if ( isset( $params['update_notice'] ) ) {
			$settings->set_latest_version();
		}

		$settings->save();

		return $this->route_settings( $request );
	}
}
