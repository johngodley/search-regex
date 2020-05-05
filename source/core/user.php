<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;

class Source_User extends Search_Source {
	public function get_columns() {
		$columns = [
			'user_nicename',
			'user_url',
			'display_name',
		];

		return $columns;
	}

	public function get_column_label( $column ) {
		$labels = [
			'user_nicename' => __( 'Nicename', 'search-regex' ),
			'user_url' => __( 'URL', 'search-regex' ),
			'display_name' => __( 'Display name', 'search-regex' ),
		];

		if ( isset( $labels[ $column ] ) ) {
			return $labels[ $column ];
		}

		return $column;
	}

	public function get_table_id() {
		return 'ID';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->users;
	}

	public function get_actions( Result $result ) {
		return [
			'edit' => get_edit_profile_url( $result->get_row_id(), 'admin' ),
		];
	}

	public function get_title_column() {
		return 'user_nicename';
	}

	public function save( $row_id, $column_id, $content ) {
		// This does all the sanitization
		wp_update_user( [
			'ID' => $row_id,
			$column_id => $content,
		] );
	}

	public function delete_row( $row_id ) {
		if ( wp_delete_user( $row_id ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex_delete', 'Failed to delete user', 401 );
	}
}
