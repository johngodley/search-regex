<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;

class Source_Post extends Search_Source {
	/**
	 * Array of supported custom post types
	 *
	 * @var Array
	 */
	private $cpts = [];

	/**
	 * Set all the custom post types that this source supports
	 *
	 * @param Array $cpts Array of custom post type names.
	 * @return void
	 */
	public function set_custom_post_types( array $cpts ) {
		$this->cpts = $cpts;
	}

	/**
	 * Get the custom post type from the source
	 *
	 * @param String $post_type Source post type.
	 * @return null|Array
	 */
	private function get_post_type( $post_type ) {
		$post_types = array_values( array_filter( Source_Manager::get_all_grouped(), function( $source ) {
			return $source['name'] === 'posttype';
		} ) );

		if ( count( $post_types ) === 1 ) {
			foreach ( $post_types[0]['sources'] as $source ) {
				if ( $source['name'] === $post_type ) {
					return $source;
				}
			}
		}

		return null;
	}

	public function get_type( array $row = [] ) {
		$post_type = isset( $row['post_type'] ) ? $this->get_post_type( $row['post_type'] ) : false;
		if ( $post_type ) {
			return $post_type['name'];
		}

		// Handle post types when it's not registered
		if ( isset( $row['post_type'] ) ) {
			return $row['post_type'];
		}

		return $this->source_type;
	}

	/**
	 * Return true if the source matches the type, false otherwise
	 *
	 * @param String $type Source type.
	 * @return boolean
	 */
	public function is_type( $type ) {
		return in_array( $type, $this->cpts, true );
	}

	public function get_name( array $row = [] ) {
		$post_type = isset( $row['post_type'] ) ? $this->get_post_type( $row['post_type'] ) : false;
		if ( $post_type ) {
			return $post_type['label'];
		}

		// Handle post types when it's not registered
		return isset( $row['post_type'] ) ? ucwords( $row['post_type'] ) : $this->source_name;
	}

	public function get_supported_flags() {
		return [
			'post_guid' => __( 'Search GUID', 'search-regex' ),
			'post_draft' => __( 'Exclude drafts', 'search-regex' ),
		];
	}

	public function get_actions( Result $result ) {
		$edit = get_edit_post_link( $result->get_row_id(), '' );
		$view = get_permalink( $result->get_row_id() );

		if ( $edit ) {
			return [
				'edit' => $edit,
				'view' => $view,
			];
		}

		return [];
	}

	public function get_search_conditions() {
		$parts = [];

		// If searching a particular post type then just look there
		if ( $this->source_type !== 'posts' ) {
			$parts[] = implode( ' OR ', array_map( function( $cpt ) {
				global $wpdb;

				return $wpdb->prepare( 'post_type=%s', $cpt );
			}, $this->cpts ) );
		}

		if ( $this->source_flags->has_flag( 'post_draft' ) ) {
			$parts[] = "post_status != 'draft'";
		}

		return implode( ' AND ', $parts );
	}

	public function get_info_columns() {
		return [ 'post_type' ];
	}

	public function get_column_label( $column, $data ) {
		$labels = [
			'post_content' => __( 'Content', 'search-regex' ),
			'post_excerpt' => __( 'Excerpt', 'search-regex' ),
			'post_title' => __( 'Title', 'search-regex' ),
			'post_name' => __( 'Slug', 'search-regex' ),
			'guid' => __( 'GUID', 'search-regex' ),
		];

		if ( isset( $labels[ $column ] ) ) {
			return $labels[ $column ];
		}

		return $column;
	}

	public function get_columns() {
		$columns = [
			'post_content',
			'post_excerpt',
			'post_title',
			'post_name',
		];

		if ( $this->source_flags->has_flag( 'post_guid' ) ) {
			$columns[] = 'guid';
		}

		return $columns;
	}

	public function get_title_column() {
		return 'post_title';
	}

	public function get_table_id() {
		return 'ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->posts;
	}

	public function save( $row_id, $column_id, $content ) {
		// wp_update_post expects slashes to be present, which are then removed
		$content = wp_slash( $content );

		// This does all the sanitization
		$result = wp_update_post( [
			'ID' => $row_id,
			$column_id => $content,
		] );

		if ( $result ) {
			return true;
		}

		return new \WP_Error( 'searchregex', 'Failed to update post.' );
	}

	public function delete_row( $row_id ) {
		if ( wp_delete_post( $row_id, true ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex_delete', 'Failed to delete post', 401 );
	}
}
