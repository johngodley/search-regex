<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;

class Source_Comment extends Search_Source {
	public function get_columns() {
		$columns = [
			'comment_author',
			'comment_author_email',
			'comment_author_url',
			'comment_content',
		];

		return $columns;
	}

	public function get_column_label( $column, $data ) {
		$labels = [
			'comment_author' => __( 'Name', 'search-regex' ),
			'comment_author_email' => __( 'Email', 'search-regex' ),
			'comment_author_url' => __( 'URL', 'search-regex' ),
			'comment_content' => __( 'Comment', 'search-regex' ),
		];

		if ( isset( $labels[ $column ] ) ) {
			return $labels[ $column ];
		}

		return $column;
	}

	public function get_search_conditions() {
		global $wpdb;

		// If searching a particular post type then just look there
		if ( ! $this->source_flags->has_flag( 'comment_spam' ) ) {
			return 'comment_approved=1';
		}

		return '';
	}

	public function get_actions( Result $result ) {
		$id = $result->get_row_id();
		$link = get_edit_comment_link( $id );
		$comment = get_comment( $id );
		$raw = $result->get_raw();

		if ( $link && is_object( $comment ) ) {
			$view = get_comment_link( $comment );

			return array_filter( [
				'edit' => str_replace( '&amp;', '&', $link ),
				'view' => $view,
			] );
		}

		return [];
	}

	public function get_supported_flags() {
		return [
			'comment_spam' => __( 'Include spam comments', 'search-regex' ),
		];
	}

	public function get_table_id() {
		return 'comment_ID';
	}

	public function get_info_columns() {
		return [ 'comment_post_ID' ];
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->comments;
	}

	public function get_title_column() {
		return 'comment_author';
	}

	public function save( $row_id, $column_id, $content ) {
		// wp_update_comment expects slashes to be present, which are then removed
		$content = wp_slash( $content );

		// This does all the sanitization
		$result = wp_update_comment( [
			$this->get_table_id() => $row_id,
			$column_id => $content,
		] );

		if ( $result ) {
			return true;
		}

		return new \WP_Error( 'searchregex', 'Failed to save comment' );
	}

	public function delete_row( $row_id ) {
		if ( wp_delete_comment( $row_id, true ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex_delete', 'Failed to delete comment', 401 );
	}
}
