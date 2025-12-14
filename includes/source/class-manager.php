<?php

namespace SearchRegex\Source;

use SearchRegex\Schema;
use SearchRegex\Filter;

/**
 * Create Source objects
 *
 * @phpstan-type SourceConfig array{name: string, class: string, label: string, type: string}
 * @phpstan-type SourceGroup array{name: string, label: string, sources: list<SourceConfig>}
 * @phpstan-type FilterConfig array{column?: string, logic?: string, value?: mixed, type?: string}
 */
class Manager {
	/**
	 * Return all the core source types
	 *
	 * @return list<SourceConfig>
	 */
	private static function get_core_sources() {
		$sources = [
			[
				'name' => 'posts',
				'class' => 'SearchRegex\Source\Core\Post',
				'label' => __( 'Posts (core & custom)', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'comment',
				'class' => 'SearchRegex\Source\Core\Comment',
				'label' => __( 'Comments', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'user',
				'class' => 'SearchRegex\Source\Core\User',
				'label' => __( 'Users', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'options',
				'class' => 'SearchRegex\Source\Core\Options',
				'label' => __( 'WordPress Options', 'search-regex' ),
				'type' => 'core',
			],
		];

		return apply_filters( 'searchregex_sources_core', $sources );
	}

	/**
	 * Return all the advanced source types
	 *
	 * @return list<SourceConfig>
	 */
	private static function get_advanced_sources() {
		$sources = [
			[
				'name' => 'post-meta',
				'class' => 'SearchRegex\Source\Core\Post_Meta',
				'label' => __( 'Post Meta', 'search-regex' ),
				'type' => 'advanced',
			],
			[
				'name' => 'comment-meta',
				'class' => 'SearchRegex\Source\Core\Comment_Meta',
				'label' => __( 'Comment Meta', 'search-regex' ),
				'type' => 'advanced',
			],
			[
				'name' => 'user-meta',
				'class' => 'SearchRegex\Source\Core\User_Meta',
				'label' => __( 'User Meta', 'search-regex' ),
				'type' => 'advanced',
			],
			[
				'name' => 'terms',
				'class' => 'SearchRegex\Source\Core\Terms',
				'label' => __( 'Terms', 'search-regex' ),
				'type' => 'advanced',
			],
		];

		return apply_filters( 'searchregex_sources_advanced', $sources );
	}

	/**
	 * Return an array of all the database sources. Note this is filtered with `searchregex_sources`
	 *
	 * @return list<SourceConfig>
	 */
	public static function get_all_sources() {
		$core_sources = self::get_core_sources();
		$advanced_sources = self::get_advanced_sources();

		// Load custom stuff here
		$plugin_sources = glob( __DIR__ . '/plugin/*.php' );

		if ( is_array( $plugin_sources ) ) {
			foreach ( $plugin_sources as $plugin ) {
				require_once $plugin;
			}
		}

		$plugin_sources = apply_filters( 'searchregex_sources_plugin', [] );
		$plugin_sources = array_map(
			function ( $source ) {
				$source['type'] = 'plugin';
				return $source;
			}, $plugin_sources
		);

		return array_values(
			array_merge(
				array_values( $core_sources ),
				array_values( $advanced_sources ),
				array_values( $plugin_sources )
			)
		);
	}

	/**
	 * Get schema for a list of sources
	 *
	 * @param string[] $sources Sources.
	 * @return list<array<string, mixed>>
	 */
	public static function get_schema( array $sources = [] ) {
		$all = self::get_all_source_names();
		$handlers = self::get( $all, [] );
		$schema = [];

		foreach ( $handlers as $source ) {
			$newschema = $source->get_schema_for_source();
			$newschema['type'] = $source->get_type() === 'post' ? 'posts' : $source->get_type();

			if ( count( $sources ) === 0 || in_array( $source->get_type(), $sources, true ) ) {
				$schema[] = $newschema;
			}
		}

		return apply_filters( 'searchregex_schema_source', $schema );
	}

	/**
	 * Get all the sources grouped into 'core', 'posttype', and 'plugin' groups.
	 *
	 * @return list<SourceGroup>
	 */
	public static function get_all_grouped() {
		$sources = self::get_all_sources();

		$groups = [
			[
				'name' => 'core',
				'label' => __( 'Standard', 'search-regex' ),
				'sources' => array_values(
					array_filter(
						$sources, function ( $source ) {
							return $source['type'] === 'core';
						}
					)
				),
			],
			[
				'name' => 'advanced',
				'label' => __( 'Advanced', 'search-regex' ),
				'sources' => array_values(
					array_filter(
						$sources, function ( $source ) {
							return $source['type'] === 'advanced';
						}
					)
				),
			],
			[
				'name' => 'plugin',
				'label' => __( 'Plugins', 'search-regex' ),
				'sources' => array_values(
					array_filter(
						$sources, function ( $source ) {
							return $source['type'] === 'plugin';
						}
					)
				),
			],
		];

		return array_values(
			array_filter(
				apply_filters( 'searchregex_source_groups', $groups ), function ( $group ) {
					return count( $group['sources'] ) > 0;
				}
			)
		);
	}

	/**
	 * Return a particular Source object for the given name
	 *
	 * @param string $source Source name.
	 * @param array<Filter\Filter> $filters Search filters.
	 * @return Source|null
	 */
	private static function get_handler_for_source( $source, array $filters ) {
		$sources = self::get_all_sources();

		foreach ( $sources as $handler ) {
			if ( $handler['name'] === $source ) {
				// Only use the filters for this source
				$filters = array_filter(
					$filters, function ( $filter ) use ( $source ) {
						return $filter->is_for_source( $source );
					}
				);

				// Create the source
				/** @var Source $instance */
				$instance = new $handler['class']( $handler, $filters );
				return $instance;
			}
		}

		return null;
	}

	/**
	 * Return a list of all source names only. This can be used for checking a name is allowed.
	 *
	 * @return string[] Array of source names
	 */
	public static function get_all_source_names() {
		$sources = self::get_all_sources();

		return array_map(
			function ( $source ) {
				return $source['name'];
			}, $sources
		);
	}

	/**
	 * Get all the specified sources as source objects
	 *
	 * @param string[] $sources Array of source names.
	 * @param array<Filter\Filter> $filters Search filters.
	 * @return Source[] The array of source objects
	 */
	public static function get( $sources, array $filters ) {
		$handlers = [];

		// Create handlers for everything else
		foreach ( $sources as $source ) {
			$handler = self::get_handler_for_source( $source, $filters );

			if ( $handler ) {
				$handlers[] = $handler;
			}
		}

		return $handlers;
	}

	/**
	 * Get preload data for a source.
	 *
	 * @param string $source_name Source.
	 * @param FilterConfig $filter Filter JSON.
	 * @return list<array{label:string, value:string}>
	 */
	public static function get_schema_preload( $source_name, $filter ) {
		$source = self::get_handler_for_source( $source_name, [] );

		if ( $source ) {
			$schema = $source->get_schema();

			foreach ( $schema['columns'] as $column ) {
				if ( isset( $filter['column'] ) && $column['column'] === $filter['column'] ) {
					$preload = false;
					if ( $column['type'] === 'member' ) {
						$preload = true;
					} elseif ( $column['type'] === 'integer' && ( ! isset( $filter['logic'] ) || ( $filter['logic'] === 'equals' || $filter['logic'] === 'notequals' ) ) ) {
						$preload = true;
					}

					if ( $preload ) {
						$schema = new Schema\Source( $source->get_schema() );
						$filter = Filter\Type\Filter_Type::create( $filter, new Schema\Column( $column, $schema ) );

						if ( $filter ) {
							return $source->get_filter_preload( $column, $filter );
						}
					}
				}
			}
		}

		return [];
	}
}
