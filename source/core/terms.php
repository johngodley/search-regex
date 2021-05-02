<?php

namespace SearchRegex;

use SearchRegex\Search_Source;
use SearchRegex\Result;
use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Value;

/**
 * Term source
 */
class Source_Terms extends Search_Source {
	public function get_table_id() {
		global $wpdb;

		return 'term_id';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'terms';
	}

	public function get_title_column() {
		return 'name';
	}

	public function save( $row_id, array $updates ) {
		$term = $this->get_columns_to_change( $updates );

		if ( count( $term ) > 0 ) {
			$existing = get_term( $row_id );
			if ( $existing === null || $existing instanceof WP_Error ) {
				return new \WP_Error( 'searchregex', 'Failed to update term.' );
			}

			if ( isset( $term['name'] ) ) {
				$term['description'] = $term['name'];
				unset( $term['name'] );
			}

			$this->log_save( 'term', array_merge( [ 'term_id' => $row_id ], $term ) );

			// This does all the sanitization
			$result = true;

			if ( searchregex_can_save() ) {
				$result = wp_update_term( $row_id, $existing->taxonomy, $term );
			}

			if ( $result ) {
				return true;
			}

			return new \WP_Error( 'searchregex', 'Failed to update term.' );
		}

		return true;
	}

	public function delete_row( $row_id ) {
		$this->log_save( 'delete term', $row_id );

		if ( searchregex_can_save() ) {
			$term = get_term( $row_id );
			if ( $term ) {
				if ( wp_delete_term( $row_id, $term->taxonomy ) ) {
					return true;
				}
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete term', 401 );
		}

		return true;
	}

	public function autocomplete( $column, $value ) {
		global $wpdb;

		if ( $column['column'] === 'name' ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT name as id,name as value FROM {$this->get_table_name()} WHERE name LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		if ( $column['column'] === 'slug' ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT slug as id,slug as value FROM {$this->get_table_name()} WHERE slug LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		$taxonomies = [];
		$taxes = get_taxonomies( '', 'object' );

		foreach ( $taxes as $tax ) {
			$taxonomies[] = [ 'value' => $tax->name, 'label' => $tax->label ];
		}

		return [
			'name' => __( 'Terms', 'search-regex' ),
			'table' => $wpdb->prefix . 'terms',
			'columns' => [
				[
					'column' => 'term_id',
					'type' => 'integer',
					'title' => __( 'ID', 'search-regex' ),
					'options' => 'api',
					'modify' => false,
				],
				[
					'column' => 'name',
					'type' => 'string',
					'title' => __( 'Name', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'slug',
					'type' => 'string',
					'title' => __( 'Slug', 'search-regex' ),
					'options' => 'api',
					'global' => true,
				],
				[
					'column' => 'taxonomy',
					'type' => 'member',
					'title' => __( 'Taxonomy', 'search-regex' ),
					'options' => $taxonomies,
					'join' => 'taxonomy',
					'modify' => false,
				],
			],
		];
	}
}
