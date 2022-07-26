<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Search;
use SearchRegex\Api;

/**
 * @api {get} /search-regex/v1/preset Get presets
 * @apiVersion 1.0.0
 * @apiName GetPresets
 * @apiDescription Get a list of presets
 *
 * @apiGroup Preset
 *
 * @apiUse Presets
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/preset Create preset
 * @apiVersion 1.0.0
 * @apiName CreatePreset
 * @apiDescription Create a new preset
 *
 * @apiGroup Preset
 *
 * @apiUse Presets
 * @apiUse Preset
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/preset/:id Update preset
 * @apiVersion 1.0.0
 * @apiName UpdatePreset
 * @apiDescription Update an existing preset
 *
 * @apiParam (URL) {String} :id The preset ID
 *
 * @apiGroup Preset
 *
 * @apiUse Presets
 * @apiUse Preset
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/preset/:id/delete Delete preset
 * @apiVersion 1.0.0
 * @apiName DeletePreset
 * @apiDescription Delete an existing preset
 *
 * @apiParam (URL) {String} :id The preset ID
 *
 * @apiGroup Preset
 *
 * @apiUse Presets
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @api {post} /search-regex/v1/preset/import Import presets
 * @apiVersion 1.0.0
 * @apiName ImportPreset
 * @apiDescription Import presets
 *
 * @apiGroup Preset
 * @apiSuccess {File} file
 * @apiUse Presets
 * @apiUse Preset
 * @apiUse 401Error
 * @apiUse 404Error
 */

/**
 * @apiDefine Presets
 *
 * @apiSuccess {Array} presets
 * @apiSuccess {String} presets.id Preset ID
 * @apiSuccess {String} presets.name Preset name
 * @apiSuccess {String} presets.description Preset description
 * @apiSuccess {Object} presets.search Search presets
 * @apiSuccess {String} presets.search.searchPhrase The search phrase
 * @apiSuccess {String|null} presets.search.replacement The replacement phrase, or null to remove the phrase
 * @apiSuccess {Integer} presets.search.perPage Per page values
 * @apiSuccess {String[]} presets.search.searchFlags Search flags
 * @apiSuccess {String[]} presets.search.sourceFlags Source flags
 * @apiSuccess {String[]} presets.search.source Array of source names
 * @apiSuccess {String[]} presets.locked
 * @apiSuccess {Object} presets.tags
 * @apiSuccess {String} presets.tags.name
 * @apiSuccess {String} presets.tags.title
 */

/**
 * @apiDefine Preset
 *
 * @apiSuccess {Object} current
 * @apiSuccess {String} current.id Preset ID
 * @apiSuccess {String} current.name Preset name
 * @apiSuccess {String} current.description Preset description
 * @apiSuccess {Object} current.search Search presets
 * @apiSuccess {String} current.search.searchPhrase The search phrase
 * @apiSuccess {String|null} current.search.replacement The replacement phrase, or null to remove the phrase
 * @apiSuccess {Integer} current.search.perPage Per page values
 * @apiSuccess {String[]} current.search.searchFlags Search flags
 * @apiSuccess {String[]} current.search.sourceFlags Source flags
 * @apiSuccess {String[]} current.search.source Array of source names
 * @apiSuccess {String[]} current.locked
 * @apiSuccess {Object} current.tags
 * @apiSuccess {String} current.tags.name
 * @apiSuccess {String} current.tags.title
 */
/**
 * Search API endpoint
 */
class Preset_Route extends Api\Route {
	/**
	 * Get preset API params
	 *
	 * @return array
	 */
	private function get_preset_params() {
		$search = $this->get_search_params();

		$search['searchPhrase']['type'] = 'string|null';
		unset( $search['searchPhrase']['required'] );
		unset( $search['source']['required'] );
		unset( $search['searchPhrase']['validate_callback'] );

		return array_merge(
			[
				'name' => [
					'description' => 'Name for the preset',
					'type' => 'string',
					'required' => true,
				],
				'description' => [
					'description' => 'Preset description',
					'type' => 'string',
					'required' => false,
				],
				'locked' => [
					'description' => 'List of locked fields',
					'type' => 'array',
					'items' => [
						'type' => 'string',
					],
					'validate_callback' => [ $this, 'validate_locked' ],
					'required' => false,
				],
				'tags' => [
					'description' => 'Array of tags',
					'type' => 'array',
					'items' => [
						'type' => 'object',
						'properties' => [
							'name' => [ 'type' => 'string' ],
							'title' => [ 'type' => 'string' ],
						],
					],
					'validate_callback' => [ $this, 'validate_tags' ],
					'required' => false,
				],
			],
			$search
		);
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param String $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route( $namespace, '/preset', [
			$this->get_route( \WP_REST_Server::READABLE, 'route_list', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/preset', [
			'args' => $this->get_preset_params(),
			$this->get_route( \WP_REST_Server::EDITABLE, 'route_create', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/preset/import', [
			$this->get_route( \WP_REST_Server::EDITABLE, 'route_import', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/preset/id/(?P<id>[A-Za-z0-9]+)', [
			'args' => $this->get_preset_params(),
			$this->get_route( \WP_REST_Server::EDITABLE, 'route_update', [ $this, 'permission_callback' ] ),
		] );

		register_rest_route( $namespace, '/preset/id/(?P<id>[A-Za-z0-9]+)/delete', [
			$this->get_route( \WP_REST_Server::EDITABLE, 'route_delete', [ $this, 'permission_callback' ] ),
		] );
	}

	/**
	 * Create a new preset
	 *
	 * @param \WP_REST_Request $request API request.
	 * @return array|\WP_Error
	 */
	public function route_create( \WP_REST_Request $request ) {
		$params = $request->get_params();

		$preset = new Search\Preset( $params );
		$preset->create();

		return [
			'current' => $preset->to_json(),
			'presets' => Search\Preset::get_all(),
		];
	}

	/**
	 * Import presets from an upload
	 *
	 * @param \WP_REST_Request $request API request.
	 * @return array|\WP_Error
	 */
	public function route_import( \WP_REST_Request $request ) {
		$upload = $request->get_file_params();
		$upload = isset( $upload['file'] ) ? $upload['file'] : false;

		if ( $upload && is_uploaded_file( $upload['tmp_name'] ) ) {
			$imported = Search\Preset::import( $upload['tmp_name'] );

			if ( $imported > 0 ) {
				return [
					'presets' => Search\Preset::get_all(),
					'import' => $imported,
				];
			}
		}

		return new \WP_Error( 'searchregex_import_preset', 'Invalid import file' );
	}

	/**
	 * Update an existing preset
	 *
	 * @param \WP_REST_Request $request API request.
	 * @return array|\WP_Error
	 */
	public function route_update( \WP_REST_Request $request ) {
		$params = $request->get_params();

		$preset = Search\Preset::get( $params['id'] );
		if ( $preset ) {
			$preset->update( $params );

			return [
				'current' => $preset->to_json(),
				'presets' => Search\Preset::get_all(),
			];
		}

		return new \WP_Error( 'searchregex', 'No preset of that ID' );
	}

	/**
	 * Delete an existing preset
	 *
	 * @param \WP_REST_Request $request API request.
	 * @return array|\WP_Error
	 */
	public function route_delete( \WP_REST_Request $request ) {
		$params = $request->get_params();

		$preset = Search\Preset::get( $params['id'] );
		if ( $preset ) {
			$preset->delete();

			return [
				'current' => $preset->to_json(),
				'presets' => Search\Preset::get_all(),
			];
		}

		return new \WP_Error( 'searchregex', 'No preset of that ID' );
	}

	/**
	 * Return a list of presets
	 *
	 * @param \WP_REST_Request $request API request.
	 * @return array
	 */
	public function route_list( \WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( isset( $params['force'] ) ) {
			header( 'Content-Type: application/json' );
			header( 'Content-Transfer-Encoding: Binary' );
			header( 'Content-disposition: attachment; filename="presets.json"' );

			return array_map( function( $preset ) {
				unset( $preset['id'] );

				if ( empty( $preset['tags'] ) ) {
					unset( $preset['tags'] );
				}

				if ( empty( $preset['locked'] ) ) {
					unset( $preset['locked'] );
				}

				return $preset;
			}, Search\Preset::get_all() );
		}

		return [
			'presets' => Search\Preset::get_all(),
		];
	}

	/**
	 * Validate that the locked params are valid
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return \WP_Error|Bool true or false
	 */
	public function validate_locked( $value, \WP_REST_Request $request, $param ) {
		$preset = new Search\Preset();

		if ( is_array( $value ) ) {
			$filtered = array_filter( $value, function( $item ) use ( $preset ) {
				return in_array( $item, $preset->get_allowed_fields(), true );
			} );

			if ( count( $value ) === count( $filtered ) ) {
				return true;
			}
		}

		return new \WP_Error( 'rest_invalid_param', 'Invalid locked params', array( 'status' => 400 ) );
	}

	/**
	 * Validate that the tag params are valid
	 *
	 * @param Array|String     $value The value to validate.
	 * @param \WP_REST_Request $request The request.
	 * @param Array            $param The array of parameters.
	 * @return \WP_Error|Bool true or false
	 */
	public function validate_tags( $value, \WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			$filtered = array_filter( $value, function( $item ) {
				return isset( $item['name'] ) && isset( $item['title'] );
			} );

			if ( count( $value ) === count( $filtered ) ) {
				return true;
			}
		}

		return new \WP_Error( 'rest_invalid_param', 'Invalid tag params', array( 'status' => 400 ) );
	}
}
