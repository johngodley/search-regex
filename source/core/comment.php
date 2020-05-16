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

	public function get_column_label( $column ) {
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
		$link = get_edit_comment_link( $result->get_row_id() );
		$raw = $result->get_raw();

		if ( $link ) {
			$view = get_permalink( intval( $raw['comment_post_ID'], 10 ) );

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
