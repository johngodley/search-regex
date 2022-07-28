<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Plugin;

abstract class Meta extends Source\Source {
	public function get_table_id() {
		return 'meta_id';
	}

	public function get_title_column() {
		return 'meta_key';
	}

	/**
	 * Label for the meta-data
	 *
	 * @return string
	 */
	abstract public function get_meta_name();

	/**
	 * Return the meta object ID name
	 *
	 * @return String
	 */
	abstract public function get_meta_object_id();

	/**
	 * Return the meta table name
	 *
	 * @return String
	 */
	abstract public function get_meta_table();

	public function save( $row_id, array $changes ) {
		global $wpdb;

		$meta = $this->get_columns_to_change( $changes );

		if ( count( $meta ) > 0 ) {
			$this->log_save( 'meta', $meta );

			// This does all the sanitization
			$result = true;

			/** @psalm-suppress UndefinedFunction */
			if ( Plugin\Settings::init()->can_save() ) {
				$result = $wpdb->update( _get_meta_table( $this->get_meta_table() ), $meta, [ $this->get_table_id() => $row_id ] );
				if ( $result === false ) {
					return new \WP_Error( 'searchregex', 'Failed to update meta data: ' . $this->get_meta_table() );
				}

				// Clear any cache
				wp_cache_delete( $this->get_meta_object_id(), $this->get_meta_table() . '_meta' );
			}

			return $result;
		}

		return true;
	}

	public function delete_row( $row_id ) {
		global $wpdb;

		$this->log_save( 'delete meta', $row_id );

		/** @psalm-suppress UndefinedFunction */
		if ( Plugin\Settings::init()->can_save() ) {
			$result = $wpdb->delete( $this->get_table_name(), [ $this->get_table_id() => $row_id ] );

			if ( $result ) {
				wp_cache_delete( $this->get_meta_object_id(), $this->get_meta_table() . '_meta' );
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete meta', 401 );
		}

		return true;
	}

	/**
	 * Perform autocompletion on a column and a value
	 *
	 * @param array  $column Column.
	 * @param string $value  Value.
	 * @return array
	 */
	public function autocomplete( array $column, $value ) {
		global $wpdb;

		if ( in_array( $column['column'], [ 'meta_key', 'meta_value' ], true ) ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT " . $column['column'] . " as id," . $column['column'] . " as value FROM {$this->get_table_name()} WHERE " . $column['column'] . " LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		return [];
	}

	public function get_schema() {
		return [
			'name' => $this->get_meta_name(),
			'table' => $this->get_table_name(),
			'columns' => [
				[
					'column' => $this->get_meta_object_id(),
					'type' => 'integer',
					'title' => __( 'Owner ID', 'search-regex' ),
					'options' => 'api',
					'joined_by' => $this->get_meta_table(),
				],
				[
					'column' => 'meta_key',
					'type' => 'string',
					'title' => __( 'Meta Key', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'meta_value',
					'type' => 'string',
					'title' => __( 'Meta Value', 'search-regex' ),
					'options' => 'api',
					'multiline' => true,
					'global' => true,
				],
			],
		];
	}
}
