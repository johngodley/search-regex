<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Source_Options extends Search_Source {
	public function get_columns() {
		$columns = [
			'option_name',
			'option_value',
		];

		return $columns;
	}

	public function get_column_label( $column, $data ) {
		$labels = [
			'option_name' => __( 'Name', 'search-regex' ),
			'option_value' => __( 'Value', 'search-regex' ),
		];

		if ( isset( $labels[ $column ] ) ) {
			return $labels[ $column ];
		}

		return $column;
	}

	public function get_table_id() {
		return 'option_id';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->options;
	}

	public function get_title_column() {
		return 'option_name';
	}

	public function save( $row_id, $column_id, $content ) {
		global $wpdb;

		// Get current option name. The table name is a known sanitized value
		// phpcs:ignore
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$this->get_table_name()} WHERE option_id=%d", $row_id ) );
		if ( ! $row ) {
			return new \WP_Error( 'searchregex', 'Unable to update option' );
		}

		if ( $column_id === 'option_name' ) {
			// Changing the option name. Delete the current option and then recreate with the new option. This ensures it is correctly sanitized
			delete_option( $row->option_name );

			// Insert as a new option
			if ( add_option( $content, $row->option_value, '', $row->autoload ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Unable to update option' );
		}

		// This handles all sanitization
		if ( update_option( $row->option_name, $content ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex', 'Unable to update option' );
	}

	public function delete_row( $row_id ) {
		global $wpdb;

		// Get current option name. The table name is a known sanitized value
		// phpcs:ignore
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$this->get_table_name()} WHERE option_id=%d", $row_id ) );
		if ( ! $row ) {
			return new \WP_Error( 'searchregex', 'Failed to delete option' );
		}

		if ( delete_option( $row->option_name ) ) {
			return true;
		}

		return new \WP_Error( 'searchregex_delete', 'Failed to delete option', 401 );
	}
}
