<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Search;
use SearchRegex\Api;
use WP_REST_Request;
use WP_Error;

/**
 * Search API endpoint
 */
class Preset_Route extends Api\Route {
	/**
	 * Get preset API params
	 *
	 * @return array<string, mixed>
	 */
	private function get_preset_params() {
		$search = $this->get_search_params();

		$search['searchPhrase']['type'] = 'string|null';
		unset( $search['searchPhrase']['required'] );
		unset( $search['source']['required'] );
		unset( $search['searchPhrase']['validate_callback'] );

		$base_params = [
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
		];

		return array_merge( $base_params, $search );
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param string $namespace Namespace.
	 */
	public function __construct( $namespace ) {
		register_rest_route(
			$namespace,
			'/preset',
			[
				$this->get_route( \WP_REST_Server::READABLE, 'route_list', [ $this, 'permission_callback' ] ),
			]
		);

		register_rest_route(
			$namespace,
			'/preset',
			[
				array_merge(
					[ 'args' => $this->get_preset_params() ],
					$this->get_route( \WP_REST_Server::EDITABLE, 'route_create', [ $this, 'permission_callback' ] )
				),
			]
		);

		register_rest_route(
			$namespace,
			'/preset/import',
			[
				$this->get_route( \WP_REST_Server::EDITABLE, 'route_import', [ $this, 'permission_callback' ] ),
			]
		);

		register_rest_route(
			$namespace,
			'/preset/id/(?P<id>[A-Za-z0-9]+)',
			[
				array_merge(
					[ 'args' => $this->get_preset_params() ],
					$this->get_route( \WP_REST_Server::EDITABLE, 'route_update', [ $this, 'permission_callback' ] )
				),
			]
		);

		register_rest_route(
			$namespace,
			'/preset/id/(?P<id>[A-Za-z0-9]+)/delete',
			[
				$this->get_route( \WP_REST_Server::EDITABLE, 'route_delete', [ $this, 'permission_callback' ] ),
			]
		);
	}

	/**
	 * Create a new preset
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request API request.
	 * @return array<string, mixed>|WP_Error
	 */
	public function route_create( WP_REST_Request $request ) {
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
	 * @param WP_REST_Request<array<string, mixed>> $request API request.
	 * @return array<string, mixed>|WP_Error
	 */
	public function route_import( WP_REST_Request $request ) {
		$upload = $request->get_file_params();
		$upload = $upload['file'] ?? false;

		if ( $upload && is_uploaded_file( $upload['tmp_name'] ) ) {
			$imported = Search\Preset::import( $upload['tmp_name'] );

			if ( $imported > 0 ) {
				return [
					'presets' => Search\Preset::get_all(),
					'import' => $imported,
				];
			}
		}

		return new WP_Error( 'searchregex_import_preset', 'Invalid import file', [ 'status' => 400 ] );
	}

	/**
	 * Update an existing preset
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request API request.
	 * @return array<string, mixed>|WP_Error
	 */
	public function route_update( WP_REST_Request $request ) {
		$params = $request->get_params();

		$preset = Search\Preset::get( $params['id'] );
		if ( $preset ) {
			$preset->update( $params );

			return [
				'current' => $preset->to_json(),
				'presets' => Search\Preset::get_all(),
			];
		}

		return new WP_Error( 'searchregex', 'No preset of that ID', [ 'status' => 404 ] );
	}

	/**
	 * Delete an existing preset
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request API request.
	 * @return array<string, mixed>|WP_Error
	 */
	public function route_delete( WP_REST_Request $request ) {
		$params = $request->get_params();

		$preset = Search\Preset::get( $params['id'] );
		if ( $preset ) {
			$preset->delete();

			return [
				'current' => $preset->to_json(),
				'presets' => Search\Preset::get_all(),
			];
		}

		return new WP_Error( 'searchregex', 'No preset of that ID' );
	}

	/**
	 * Return a list of presets
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request API request.
	 * @return array<string, mixed>
	 */
	public function route_list( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( isset( $params['force'] ) ) {
			header( 'Content-Type: application/json' );
			header( 'Content-Transfer-Encoding: Binary' );
			header( 'Content-disposition: attachment; filename="presets.json"' );

			return array_map(
				function ( $preset ) {
					unset( $preset['id'] );

					if ( isset( $preset['tags'] ) && $preset['tags'] === [] ) {
						unset( $preset['tags'] );
					}

					if ( isset( $preset['locked'] ) && $preset['locked'] === [] ) {
						unset( $preset['locked'] );
					}

					return $preset;
				}, Search\Preset::get_all()
			);
		}

		return [
			'presets' => Search\Preset::get_all(),
		];
	}

	/**
	 * Validate that the locked params are valid
	 *
	 * @param array<string, mixed>|string $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed> $_param The array of parameters.
	 * @return WP_Error|bool true or false
	 */
	public function validate_locked( $value, WP_REST_Request $_request, $_param ) {
		$preset = new Search\Preset();

		if ( is_array( $value ) ) {
			$filtered = array_filter(
				$value, fn( $item ) => in_array( $item, $preset->get_allowed_fields(), true )
			);

			if ( count( $value ) === count( $filtered ) ) {
				return true;
			}
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid locked params', [ 'status' => 400 ] );
	}

	/**
	 * Validate that the tag params are valid
	 *
	 * @param array<string, mixed>|string $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed> $_param The array of parameters.
	 * @return WP_Error|bool true or false
	 */
	public function validate_tags( $value, WP_REST_Request $_request, $_param ) {
		if ( is_array( $value ) ) {
			$filtered = array_filter(
				$value, fn( $item ) => isset( $item['name'] ) && isset( $item['title'] )
			);

			if ( count( $value ) === count( $filtered ) ) {
				return true;
			}
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid tag params', [ 'status' => 400 ] );
	}
}
