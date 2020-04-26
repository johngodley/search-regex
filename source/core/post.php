<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Source_Post extends Search_Source {
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

	public function get_type( array $row ) {
		$post_type = isset( $row['post_type'] ) ? $this->get_post_type( $row['post_type'] ) : false;
		if ( $post_type ) {
			return $post_type['name'];
		}

		return $this->source_type;
	}

	public function get_name( array $row ) {
		$post_type = isset( $row['post_type'] ) ? $this->get_post_type( $row['post_type'] ) : false;
		if ( $post_type ) {
			return $post_type['label'];
		}

		return $this->source_name;
	}

	public function get_supported_flags() {
		return [
			'post_guid' => __( 'Search GUID', 'search-regex' ),
//			'post_revisions' => __( 'Disable post revision', 'search-regex' ),
		];
	}

	public function get_actions( $result ) {
		$edit = get_edit_post_link( $result->get_row_id(), '' );
		$view = get_permalink( $result->get_row_id() );

		if ( $edit ) {
			return [
				'edit' => get_edit_post_link( $result->get_row_id(), '' ),
				'view' => get_permalink( $result->get_row_id() ),
			];
		}

		return [];
	}

	public function get_search_conditions( $search ) {
		global $wpdb;

		// If searching a particular post type then just look there
		if ( $this->source_type !== 'posts' ) {
			return [ $wpdb->prepare( 'post_type=%s', $this->source_type ) ];
		}

		return [];
	}

	public function get_info_columns() {
		return [ 'post_type' ];
	}

	public function get_column_label( $column ) {
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
		// This does all the sanitization
		$result = wp_update_post( [
			'ID' => $row_id,
			$column_id => $content,
		] );

		if ( $result ) {
			return true;
		}

		return new WP_Error( 'searchregex', 'Failed to update post' );
	}
}
