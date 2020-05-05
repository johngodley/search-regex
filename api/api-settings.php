<?php
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

class Search_Regex_Api_Settings extends Search_Regex_Api_Route {
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/setting', array(
			$this->get_route( WP_REST_Server::READABLE, 'route_settings', [ $this, 'permission_callback' ] ),
			$this->get_route( WP_REST_Server::EDITABLE, 'route_save_settings', [ $this, 'permission_callback' ] ),
		) );
	}

	public function route_settings( WP_REST_Request $request ) {
		return [
			'settings' => searchregex_get_options(),
		];
	}

	public function route_save_settings( WP_REST_Request $request ) {
		$params = $request->get_params();

		searchregex_set_options( $params );

		return $this->route_settings( $request );
	}
}
