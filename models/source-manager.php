<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

/**
 * Create Source objects
 */
class Source_Manager {
	/**
	 * Return an array of all the database sources. Note this is filtered with `searchregex_sources`
	 *
	 * @return Array The array of database sources as name => class
	 */
	public static function get_all_sources() {
		$core_sources = [
			[
				'name' => 'posts',
				'class' => 'SearchRegex\Source_Post',
				'label' => __( 'All Post Types', 'search-regex' ),
				'description' => __( 'Search all posts, pages, and custom post types.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'post-meta',
				'class' => 'SearchRegex\Source_Post_Meta',
				'label' => __( 'Post Meta', 'search-regex' ),
				'description' => __( 'Search post meta names and values.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'comment',
				'class' => 'SearchRegex\Source_Comment',
				'label' => __( 'Comments', 'search-regex' ),
				'description' => __( 'Search comment content, URL, and author, with optional support for spam comments.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'comment-meta',
				'class' => 'SearchRegex\Source_Comment_Meta',
				'label' => __( 'Comment Meta', 'search-regex' ),
				'description' => __( 'Search comment meta names and values.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'user',
				'class' => 'SearchRegex\Source_User',
				'label' => __( 'Users', 'search-regex' ),
				'description' => __( 'Search user email, URL, and name.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'user-meta',
				'class' => 'SearchRegex\Source_User_Meta',
				'label' => __( 'User Meta', 'search-regex' ),
				'description' => __( 'Search user meta name and values.', 'search-regex' ),
				'type' => 'core',
			],
			[
				'name' => 'options',
				'class' => 'SearchRegex\Source_Options',
				'label' => __( 'WordPress Options', 'search-regex' ),
				'description' => __( 'Search all WordPress options.', 'search-regex' ),
				'type' => 'core',
			],
		];

		// Add on post types
		/** @var Array */
		$post_types = get_post_types( [], 'objects' );
		$post_sources = [];

		foreach ( $post_types as $type ) {
			if ( strlen( $type->label ) > 0 ) {
				$post_sources[] = [
					'name' => $type->name,
					'class' => 'SearchRegex\Source_Post',
					'label' => $type->label,
					'type' => 'posttype',
				];
			}
		}

		// Load custom stuff here
		$plugin_sources = glob( dirname( SEARCHREGEX_FILE ) . '/source/plugin/*.php' );
		foreach ( $plugin_sources as $plugin ) {
			/**
			 * @psalm-suppress UnresolvableInclude
			 */
			require_once $plugin;
		}

		$plugin_sources = apply_filters( 'searchregex_sources_plugin', [] );
		$plugin_sources = array_map( function( $source ) {
			$source['type'] = 'plugin';
			return $source;
		}, $plugin_sources );

		$post_sources = apply_filters( 'searchregex_sources_posttype', $post_sources );
		$core_sources = apply_filters( 'searchregex_sources_core', $core_sources );

		return array_merge( array_values( $core_sources ), array_values( $post_sources ), array_values( $plugin_sources ) );
	}

	/**
	 * Get all the sources grouped into 'core', 'posttype', and 'plugin' groups.
	 *
	 * @return Array Associative array of sources, grouped by type
	 */
	public static function get_all_grouped() {
		$sources = self::get_all_sources();

		$groups = [
			[
				'name' => 'core',
				'label' => __( 'Standard Types', 'search-regex' ),
				'sources' => array_values( array_filter( $sources, function( $source ) {
					return $source['type'] === 'core';
				} ) ),
			],
			[
				'name' => 'posttype',
				'label' => __( 'Specific Post Types', 'search-regex' ),
				'sources' => array_values( array_filter( $sources, function( $source ) {
					return $source['type'] === 'posttype';
				} ) ),
			],
			[
				'name' => 'plugin',
				'label' => __( 'Plugins', 'search-regex' ),
				'sources' => array_values( array_filter( $sources, function( $source ) {
					return $source['type'] === 'plugin';
				} ) ),
			],
		];

		return array_filter( apply_filters( 'searchregex_source_groups', $groups ), function( $group ) {
			return count( $group['sources'] ) > 0;
		} );
	}

	/**
	 * Return a particular Source object for the given name
	 *
	 * @param String       $source Source name.
	 * @param Search_Flags $search_flags Search_Flags.
	 * @param Source_Flags $source_flags Source_Flags.
	 * @return object|null New Source_Flags object
	 */
	private static function get_handler_for_source( $source, Search_Flags $search_flags, Source_Flags $source_flags ) {
		$sources = self::get_all_sources();

		foreach ( $sources as $handler ) {
			if ( $handler['name'] === $source ) {
				$new_source = new $handler['class']( $handler, $search_flags, $source_flags );
				$source_flags->set_allowed_flags( array_keys( $new_source->get_supported_flags() ) );

				return $new_source;
			}
		}

		return null;
	}

	/**
	 * Return a list of all source names only. This can be used for checking a name is allowed.
	 *
	 * @return Array Array of source names
	 */
	public static function get_all_source_names() {
		$sources = self::get_all_sources();

		return array_map( function( $source ) {
			return $source['name'];
		}, $sources );
	}

	/**
	 * Get all the specified sources as source objects
	 *
	 * @param Array        $sources Array of source names.
	 * @param Search_Flags $search_flags The search flags object.
	 * @param Source_Flags $source_flags The source flags object.
	 * @return Array The array of source objects
	 */
	public static function get( $sources, Search_Flags $search_flags, Source_Flags $source_flags ) {
		$handlers = [];

		foreach ( $sources as $source ) {
			$handler = self::get_handler_for_source( $source, $search_flags, $source_flags );

			if ( $handler ) {
				$handlers[] = $handler;
			}
		}

		return $handlers;
	}
}

require_once dirname( __DIR__ ) . '/source/core/meta.php';
require_once dirname( __DIR__ ) . '/source/core/post.php';
require_once dirname( __DIR__ ) . '/source/core/post-meta.php';
require_once dirname( __DIR__ ) . '/source/core/user.php';
require_once dirname( __DIR__ ) . '/source/core/user-meta.php';
require_once dirname( __DIR__ ) . '/source/core/comment.php';
require_once dirname( __DIR__ ) . '/source/core/comment-meta.php';
require_once dirname( __DIR__ ) . '/source/core/options.php';
