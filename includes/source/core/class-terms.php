<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Plugin;

/**
 * Term source
 */
class Terms extends Source\Source {
	public function get_table_id() {
		return 'term_id';
	}

	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'terms';
	}

	public function get_title_column() {
		return 'name';
	}

	public function save( $row_id, array $changes ) {
		$term = $this->get_columns_to_change( $changes );

		if ( count( $term ) > 0 ) {
			$update = [];
			global $wpdb;

			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT t.term_id, t.name, t.slug, tt.taxonomy
				 FROM {$wpdb->terms} t
				 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				 WHERE t.term_id = %d
				 LIMIT 1",
				$row_id
			) );

			if ( $existing === null ) {
				return new \WP_Error( 'searchregex', 'Term not found.' );
			}

			if ( isset( $term['name'] ) ) {
				$update['name'] = (string) $term['name'];
			}

			if ( isset( $term['slug'] ) ) {
				$update['slug'] = (string) $term['slug'];
			}

			$this->log_save( 'term', array_merge( [ 'term_id' => $row_id ], $term ) );

			// This does all the sanitization
			$result = true;

			if ( Plugin\Settings::init()->can_save() && count( $update ) > 0 ) {
				$result = wp_update_term( $row_id, $existing->taxonomy, $update );
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

		if ( Plugin\Settings::init()->can_save() ) {
			$term = get_term( $row_id );
			if ( $term instanceof \WP_Term && wp_delete_term( $row_id, $term->taxonomy ) ) {
				return true;
			}

			return new \WP_Error( 'searchregex_delete', 'Failed to delete term', 401 );
		}

		return true;
	}

	public function autocomplete( array $column, $value ) {
		global $wpdb;

		if ( $column['column'] === 'name' ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT name as id,name as value FROM {$this->get_table_name()} WHERE name LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		if ( $column['column'] === 'slug' ) {
			// phpcs:ignore
			return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT slug as id,slug as value FROM {$this->get_table_name()} WHERE slug LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', self::AUTOCOMPLETE_LIMIT ) );
		}

		return [];
	}

	public function get_schema() {
		global $wpdb;

		$taxonomies = [];
		$taxes = get_taxonomies( [], 'objects' );

		foreach ( $taxes as $tax ) {
			// @phpstan-ignore function.alreadyNarrowedType
			if ( is_object( $tax ) ) {
				$taxonomies[] = [
					'value' => $tax->name,
					'label' => $tax->label,
				];
			}
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
