<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;

/**
 * User source
 */
class Source_User extends Search_Source {
	/**
	 * Return an array of columns for this source
	 *
	 * @return Array The array of column names
	 */
	public function get_columns() {
		$columns = [
			'user_nicename',
			'user_url',
			'display_name',
		];

		return $columns;
	}

	/**
	 * Return a visible label for the column. This is shown to the user and should be more descriptive than the column name itself
	 *
	 * @param String $column Column name.
	 * @return String Column label
	 */
	public function get_column_label( $column, $data ) {
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
		$result = wp_update_user( [
			'ID' => $row_id,
			$column_id => $content,
		] );

		if ( $result ) {
			return true;
		}

		return new \WP_Error( 'searchregex', 'Failed to update user' );
	}

	public function delete_row( $row_id ) {
		if ( wp_delete_user( $row_id ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex_delete', 'Failed to delete user', 401 );
	}
}
