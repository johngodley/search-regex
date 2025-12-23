<?php

namespace SearchRegex\Api\Route;

use SearchRegex\Source;
use SearchRegex\Api;
use WP_REST_Request;
use WP_Error;

/**
 * Search API endpoint
 *
 * @phpstan-type SourceInfo array{name: string, label: string, type: string}
 */
class Source_Route extends Api\Route {
	const AUTOCOMPLETE_MAX = 50;
	const AUTOCOMPLETE_TRIM_BEFORE = 10;

	/**
	 * API schema for source validation.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_source_params() {
		return [
			'source' => [
				'validate_callback' => [ $this, 'validate_source' ],
				'sanitize_callback' => [ $this, 'sanitize_row_source' ],
			],
		];
	}

	/**
	 * Search API endpoint constructor
	 *
	 * @param string $route_namespace Namespace.
	 */
	public function __construct( $route_namespace ) {
		register_rest_route(
			$route_namespace,
			'/source',
			[
				$this->get_route( \WP_REST_Server::READABLE, 'getSources', [ $this, 'permission_callback' ] ),
			]
		);

		register_rest_route(
			$route_namespace,
			'/source/(?P<source>[a-z\-\_]+)/complete/(?P<column>[a-z\-\_]+)',
			array_merge(
				[
					'args' => array_merge(
						$this->get_source_params(),
						[
							'value' => [
								'description' => 'Auto complete value',
								'type' => 'string',
								'required' => true,
							],
						]
					),
				],
				$this->get_route( \WP_REST_Server::READABLE, 'autoComplete', [ $this, 'permission_callback' ] )
			)
		);

		register_rest_route(
			$route_namespace,
			'/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)',
			array_merge(
				[
					'args' => $this->get_source_params(),
				],
				$this->get_route( \WP_REST_Server::READABLE, 'loadRow', [ $this, 'permission_callback' ] ),
			)
		);

		register_rest_route(
			$route_namespace,
			'/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)',
			array_merge(
				[
					'args' => array_merge(
						[
							'replacement' => [
								'description' => 'Row replacement. A single action.',
								'type' => 'object',
								'validate_callback' => [ $this, 'validate_replacement' ],
								'required' => true,
							],
						],
						$this->get_source_params(),
						$this->get_search_params()
					),
				],
				$this->get_route( \WP_REST_Server::EDITABLE, 'saveRow', [ $this, 'permission_callback' ] ),
			)
		);

		register_rest_route(
			$route_namespace,
			'/source/(?P<source>[a-z\-\_]+)/row/(?P<rowId>[\d]+)/delete',
			array_merge(
				[
					'args' => $this->get_source_params(),
				],
				$this->get_route( \WP_REST_Server::EDITABLE, 'deleteRow', [ $this, 'permission_callback' ] ),
			)
		);
	}

	/**
	 * Sanitize the source so it's a single source in an array, suitable for use with the search functions
	 *
	 * @param string|array<string, mixed> $value Source name.
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @param string           $param Param name.
	 * @return string[]
	 */
	public function sanitize_row_source( $value, WP_REST_Request $request, $param ) {
		if ( is_array( $value ) ) {
			return array_slice( $value, 0, 1 );
		}

		return [ $value ];
	}

	/**
	 * Validate the replacement.
	 *
	 * @param string|array<string, mixed> $value Source name.
	 * @param WP_REST_Request<array<string, mixed>> $request Request object.
	 * @param string $param Param name.
	 * @return true|WP_Error
	 */
	public function validate_replacement( $value, WP_REST_Request $request, $param ) {
		$result = $this->contains_keys( [ 'column' ], $value );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( isset( $value['items'] ) ) {
			foreach ( $value['items'] as $item ) {
				$result = $this->contains_keys( [ 'value', 'key' ], $item );

				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		if ( isset( $value['values'] ) && ! is_array( $value['values'] ) ) {
			return new WP_Error( 'rest_invalid_param', 'Item is not an array', [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Get list of all sources
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|list<SourceInfo> Return an array of sources, or a WP_Error
	 */
	public function getSources( WP_REST_Request $request ) {
		$sources = Source\Manager::get_all_sources();

		return array_map(
			fn( $source ) => [
				'name' => $source['name'],
				'label' => $source['label'],
				'type' => $source['type'],
			], $sources
		);
	}

	/**
	 * Perform a replacement on a row
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|array<string, mixed> Return an array of results, or a WP_Error
	 */
	public function saveRow( WP_REST_Request $request ) {
		$params = $request->get_params();
		$replace = [
			'action' => 'modify',
			'actionOption' => [
				$params['replacement'],
			],
		];

		[$search, $action] = $this->get_search_replace( array_merge( $params, $replace ) );

		// Get the results for the search/replace
		$results = $search->get_row( $params['rowId'], $action );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		if ( count( $results ) === 0 || $results[0] === false ) {
			return new WP_Error( 'rest_invalid_param', 'No matching row', [ 'status' => 400 ] );
		}

		// Save the changes
		$results = $search->save_changes( $results[0] );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		// Get the row again, with the original search conditions
		[$search, $action] = $this->get_search_replace( $params );
		$results = $search->get_row( $params['rowId'], $action );
		if ( $results instanceof WP_Error ) {
			return $results;
		}

		return [
			'result' => $action->get_results( [ 'results' => $results ] )['results'][0],
		];
	}

	/**
	 * Load all relevant data from a source's row
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|array<string, mixed> Return an array of results, or a WP_Error
	 */
	public function loadRow( WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );
		$row = $sources[0]->get_row_columns( $params['rowId'] );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		return [
			'result' => $row,
		];
	}

	/**
	 * Delete a row of data
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|bool
	 */
	public function deleteRow( WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );

		return $sources[0]->delete_row( $params['rowId'] );
	}

	/**
	 * Autocomplete some value and return suggestions appropriate to the source and column
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request.
	 * @return WP_Error|array<string, mixed> Return an array of results, or a WP_Error
	 */
	public function autoComplete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$sources = Source\Manager::get( $params['source'], [] );

		// Validate the column
		foreach ( $sources[0]->get_schema_for_source()['columns'] as $column ) {
			if ( $column['column'] === $params['column'] && $column['options'] === 'api' ) {
				// Get autocomplete results
				$rows = $sources[0]->autocomplete( $column, $params['value'] );
				$results = [];

				foreach ( $rows as $row ) {
					$result = [
						'value' => $row->id,
						'title' => str_replace( [ '\n', '\r', '\t' ], '', $row->value ),
					];

					// Trim content to context
					if ( strlen( $result['title'] ) > self::AUTOCOMPLETE_MAX && strlen( $params['value'] ) > 0 ) {
						$pos = strpos( $result['title'], (string) $params['value'] );

						if ( $pos !== false ) {
							$result['title'] = '...' . substr( $result['title'], max( 0, $pos - self::AUTOCOMPLETE_TRIM_BEFORE ), self::AUTOCOMPLETE_MAX ) . '...';
						}
					} elseif ( strlen( $result['title'] ) > self::AUTOCOMPLETE_MAX ) {
						$result['title'] = substr( $result['title'], 0, self::AUTOCOMPLETE_MAX ) . '...';
					}

					$results[] = $result;
				}

				// Return to user
				return apply_filters( 'searchregex_autocomplete_results', $results );
			}
		}

		// Invalid column
		return new WP_Error( 'rest_invalid_param', 'Unknown column ' . $params['column'], [ 'status' => 400 ] );
	}
}
