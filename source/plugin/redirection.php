<?php

use SearchRegex\Search_Source;
use SearchRegex\Result;

class Redirection_Search_Regex extends Search_Source {
	public function get_actions( Result $result ) {
		$edit = admin_url( 'tools.php?page=redirection.php' );

		return [
			'edit' => $edit,
		];
	}

	public function get_columns() {
		$columns = [
			'url',
			'action_data',
			'title',
		];

		return $columns;
	}

	public function get_column_label( $column, $data ) {
		$labels = [
			'url' => __( 'URL', 'search-regex' ),
			'title' => __( 'Title', 'search-regex' ),
		];

		if ( isset( $labels[ $column ] ) ) {
			return $labels[ $column ];
		}

		return $column;
	}

	public function get_table_id() {
		return 'id';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'redirection_items';
	}

	public function get_title_column() {
		return 'url';
	}

	public function save( $row_id, $column_id, $content ) {
		$item = \Red_Item::get_by_id( $row_id );

		if ( ! is_wp_error( $item ) ) {
			/** @psalm-suppress PossiblyUndefinedMethod */
			$json = $item->to_json();

			if ( isset( $json[ $column_id ] ) ) {
				$json[ $column_id ] = $content;
				/** @psalm-suppress PossiblyUndefinedMethod */
				$saved = $item->update( $json );

				if ( is_wp_error( $saved ) ) {
					return $saved;
				}

				return true;
			}
		}

		return new WP_Error( 'searchregex', 'Failed to update redirection' );
	}
}

add_filter( 'searchregex_sources_plugin', function( $plugins ) {
	// Only show if Redirection is loaded
	if ( defined( 'REDIRECTION_VERSION' ) ) {
		$plugins[] = [
			'name' => 'redirection',
			'label' => __( 'Redirection', 'search-regex' ),
			'description' => __( 'Search your redirects', 'search-regex' ),
			'class' => 'Redirection_Search_Regex',
			'type' => 'plugin',
		];
	}

	return $plugins;
} );
