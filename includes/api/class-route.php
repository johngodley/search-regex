<?php

namespace SearchRegex\Api;

use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Action;
use SearchRegex\Schema;
use SearchRegex\Plugin;
use WP_REST_Request;
use WP_Error;

/**
 * Base class for Search Regex API endpoints
 *
 * @phpstan-type FilterItem array{type: string, items: mixed}
 * @phpstan-type FilterValidation string|list<FilterItem>
 */
class Route {
	/**
	 * Checks a capability
	 *
	 * @param WP_REST_Request<array<string, mixed>> $_request Request.
	 * @return bool
	 */
	public function permission_callback( WP_REST_Request $_request ) {
		return Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH );
	}

	/**
	 * Get route details
	 *
	 * @param string        $method Method name.
	 * @param string        $callback Function name.
	 * @param callable|bool $permissions Permissions callback.
	 * @return array<string, mixed>
	 */
	public function get_route( $method, $callback, $permissions = false ) {
			return [
				'methods' => $method,
				'callback' => [ $this, $callback ],
				'permission_callback' => $permissions !== false ? $permissions : [ $this, 'permission_callback' ],
			];
	}

	/**
	 * Return API search args
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_search_params() {
		return [
			'searchPhrase' => [
				'description' => 'The search phrase',
				'type' => 'string',
				'default' => '',
			],
			'source' => [
				'description' => 'The sources to search through. Currently only one source is supported',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'validate_callback' => [ $this, 'validate_source' ],
				'required' => true,
			],
			'searchFlags' => [
				'description' => 'Search flags',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_search_flags' ],
			],
			'filters' => [
				'description' => 'Search filters',
				'type' => 'array',
				'validate_callback' => [ $this, 'validate_filters' ],
				'default' => [],
			],
			'view' => [
				'description' => 'Additional columns to view',
				'type' => 'array',
				'items' => [
					'type' => 'string',
				],
				'default' => [],
				'validate_callback' => [ $this, 'validate_view' ],
			],
			'action' => [
				'description' => 'Action to perform on the search',
				'type' => 'string',
				'default' => 'nothing',
				'validate_callback' => [ $this, 'validate_action' ],
			],
			'actionOption' => [
				'description' => 'Options for the action',
				'type' => 'object',
				'default' => [],
			],
		];
	}

	/**
	 * Helper to return a search and replace object
	 *
	 * @param array<string, mixed> $params Array of params.
	 * @return array{Search\Search, Action\Action} Search and Replace objects
	 */
	protected function get_search_replace( $params ) {
		$schema = new Schema\Schema( Source\Manager::get_schema( $params['source'] ) );
		$filters = isset( $params['filters'] ) ? Filter\Filter::create( $params['filters'], $schema ) : [];

		// Create the actions for the search
		$action = Action\Action::create( $params['action'] ?? '', Action\Action::get_options( $params ), $schema );

		// Convert global search to filters
		if ( isset( $params['searchPhrase'] ) && $params['searchPhrase'] ) {
			$filters[] = new Filter\Global_Filter( $params['searchPhrase'], $params['searchFlags'] );
		}

		// Are we doing the action for real or just a dry run?
		if ( isset( $params['save'] ) && $params['save'] ) {
			$action->set_save_mode( true );
		}

		// Add any view columns
		$columns = $action->get_view_columns();
		if ( isset( $params['view'] ) ) {
			$columns = array_unique( [ ...$columns, ...$params['view'] ] );
		}

		$filters = Filter\Filter::get_missing_column_filters( $schema, $filters, $columns );

		// Get sources
		$sources = Source\Manager::get( $params['source'], $filters );

		// Create the search, using the filters
		$search = new Search\Search( $sources );

		return [ $search, $action ];
	}

	/**
	 * Validate that the search flags are correct
	 *
	 * @param array<string, mixed>|string     $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed>            $_param The array of parameters.
	 * @return WP_Error|bool true or false
	 */
	public function validate_search_flags( $value, WP_REST_Request $_request, $_param ) {
		if ( is_array( $value ) ) {
			$_flags = new Search\Flags( $value );
			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid search flag detected', [ 'status' => 400 ] );
	}

	/**
	 * Validate that the view columns are valid
	 *
	 * @param array<string, mixed>|string     $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed>            $_param The array of parameters.
	 * @return WP_Error|bool true or false
	 */
	public function validate_view( $value, WP_REST_Request $_request, $_param ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $view ) {
				$parts = explode( '__', $view );
				if ( count( $parts ) !== 2 ) {
					return new WP_Error( 'rest_invalid_param', 'Invalid view parameter', [ 'status' => 400 ] );
				}
			}

			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid view parameter', [ 'status' => 400 ] );
	}

	/**
	 * Validate that the view columns are valid
	 *
	 * @param array<string, mixed>|string     $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed>            $_param The array of parameters.
	 * @return WP_Error|bool true or false
	 */
	public function validate_action( $value, WP_REST_Request $_request, $_param ) {
		if ( in_array( $value, [ 'modify', 'replace', 'delete', 'export', 'nothing', 'action', '' ], true ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid view parameter', [ 'status' => 400 ] );
	}

	/**
	 * Validate that the source is valid
	 *
	 * @param array<string, mixed>|string     $value The value to validate.
	 * @param WP_REST_Request<array<string, mixed>> $_request The request.
	 * @param array<string, mixed>            $_param The array of parameters.
	 * @return bool|WP_Error true or false
	 */
	public function validate_source( $value, WP_REST_Request $_request, $_param ) {
		$allowed = Source\Manager::get_all_source_names();

		add_filter( 'wp_revisions_to_keep', [ $this, 'disable_post_revisions' ] );
		add_filter( 'wp_insert_post_data', [ $this, 'wp_insert_post_data' ] );

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$valid = array_filter(
			$value, fn( $item ) => array_search( $item, $allowed, true ) !== false
		);

		if ( count( $valid ) === count( $value ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', 'Invalid source detected', [ 'status' => 400 ] );
	}

	/**
	 * Validate supplied filters
	 *
	 * @param FilterValidation $value Value.
	 * @param WP_REST_Request<array<string, mixed>> $_request Request.
	 * @param array<string, mixed>            $_param Params.
	 * @return boolean
	 */
	public function validate_filters( $value, WP_REST_Request $_request, $_param ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $filter ) {
			// @phpstan-ignore function.alreadyNarrowedType
			if ( ! is_array( $filter ) ) {
				return false;
			}

			// Check type and items are present
			// @phpstan-ignore isset.offset
			if ( ! isset( $filter['type'] ) || ! isset( $filter['items'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Used to disable post revisions when updating a post
	 *
	 * @internal
	 * @return int
	 */
	public function disable_post_revisions() {
		return 0;
	}

	/**
	 * Stops wp_update_post from changing the post_modified date
	 *
	 * @internal
	 * @param array<string, mixed> $data Array of post data.
	 * @return array<string, mixed>
	 */
	public function wp_insert_post_data( $data ) {
		if ( isset( $data['post_modified'] ) ) {
			unset( $data['post_modified'] );
			unset( $data['post_modified_gmt'] );
		}

		return $data;
	}

	/**
	 * Does the array contain the supplied keys?
	 *
	 * @param string[] $keys Keys.
	 * @param array<string, mixed>|string $item Item.
	 * @return true|WP_Error
	 */
	protected function contains_keys( array $keys, $item ) {
		if ( ! is_array( $item ) ) {
			return new WP_Error( 'rest_invalid_param', 'Item is not an array', [ 'status' => 400 ] );
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $item[ $key ] ) ) {
				return new WP_Error( 'rest_invalid_param', 'Item does not contain key ' . $key, [ 'status' => 400 ] );
			}
		}

		return true;
	}
}
