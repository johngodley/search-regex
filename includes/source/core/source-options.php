<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Plugin;

/**
 * Source for WP options
 */
class Options extends Source\Source {
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

	public function save( $row_id, array $changes ) {
		global $wpdb;

		// Get current option name. The table name is a known sanitized value
		// phpcs:ignore
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$this->get_table_name()} WHERE option_id=%d", $row_id ) );
		if ( ! $row ) {
			return new \WP_Error( 'searchregex', 'Unable to update option' );
		}

		$option = $this->get_columns_to_change( $changes );

		if ( count( $option ) > 0 ) {
			$this->log_save( 'option', $option );

			// This does all the sanitization
			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				if ( isset( $option['option_name'] ) ) {
					// Changing the option name. Delete the current option and then recreate with the new option. This ensures it is correctly sanitized
					delete_option( $row->option_name );
				}

				// This handles all sanitization
				if ( update_option( $row->option_name, $option['option_value'], isset( $option['autoload'] ) ? $option['autoload'] : null ) ) {
					return true;
				}
			}

			return new \WP_Error( 'searchregex', 'Failed to update option.' );
		}

		return true;
	}

	public function delete_row( $row_id ) {
		global $wpdb;

		$this->log_save( 'delete option', $row_id );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			// Get the option name for the row. This is so we can use the WP delete_option and have the cache cleared
			// phpcs:ignore
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$this->get_table_name()} WHERE option_id=%d", $row_id ) );
			if ( $row ) {
				if ( delete_option( $row->option_name ) ) {
					return true;
				}
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete option', 401 );
		}

		return true;
	}

	public function autocomplete( array $column, $value ) {
		global $wpdb;

		/** @psalm-suppress InvalidArrayOffset */
		if ( in_array( $column['column'], [ 'option_name', 'option_value' ], true ) ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT " . $column['column'] . " as id," . $column['column'] . " as value FROM {$wpdb->options} WHERE " . $column['column'] . " LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		return [
			'name' => __( 'Options', 'search-regex' ),
			'table' => $wpdb->options,
			'columns' => [
				[
					'column' => 'option_id',
					'type' => 'integer',
					'title' => __( 'ID', 'search-regex' ),
					'modify' => false,
				],
				[
					'column' => 'option_name',
					'type' => 'string',
					'title' => __( 'Name', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'option_value',
					'type' => 'string',
					'title' => __( 'Value', 'search-regex' ),
					'options' => 'api',
					'multiline' => true,
					'global' => true,
				],
				[
					'column' => 'autoload',
					'type' => 'member',
					'options' => [
						[
							'value' => 'yes',
							'label' => __( 'Is autoload', 'search-regex' ),
						],
						[
							'value' => 'no',
							'label' => __( 'Is not autoload', 'search-regex' ),
						],
					],
					'title' => __( 'Autoload', 'search-regex' ),
					'multiple' => false,
				],
			],
		];
	}
}
