<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

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

	public function get_search_conditions( $search ) {
		global $wpdb;

		// If searching a particular post type then just look there
		if ( ! $this->source_flags->has_flag( 'comment_spam' ) ) {
			return [ 'comment_approved=1' ];
		}

		return [];
	}

	public function get_actions( $result ) {
		return [
			'edit' => str_replace( '&amp;', '&', get_edit_comment_link( $result->get_row_id() ) ),
		];
	}

	public function get_supported_flags() {
		return [
			'comment_spam' => __( 'Include spam comments', 'search-regex' ),
		];
	}

	public function get_table_id() {
		return 'comment_ID';
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
		wp_update_comment( [
			$this->get_table_id() => $row_id,
			$column_id => $content,
		] );
	}
}
