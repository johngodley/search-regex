<?php

namespace SearchRegex\Source;

use SearchRegex\Sql;

/**
 * Provides autocomplete functions
 *
 * @psalm-immutable
 */
class Autocomplete {
	/**
	 * Autocomplete a user name
	 *
	 * @param string $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_user( $value ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID as id,display_name as value FROM {$wpdb->users} WHERE display_name LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Source::AUTOCOMPLETE_LIMIT ) );
	}

	/**
	 * Autocomplete a post title
	 *
	 * @param string    $value Value.
	 * @param Sql\Value $id_column ID column.
	 * @param Sql\Value $search_column Search column.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_post( $value, Sql\Value $id_column, Sql\Value $search_column ) {
		global $wpdb;

		$type_sql = "AND post_status != 'inherit'";  // Ignore attachments

		$column = $id_column->get_value();
		$search = $search_column->get_value();

		// phpcs:ignore
		return $wpdb->get_results( $wpdb->prepare( "SELECT {$column} as id,post_title as value FROM {$wpdb->posts} WHERE {$search} LIKE %s {$type_sql} LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Source::AUTOCOMPLETE_LIMIT ) );
	}

	/**
	 * Autocomplete a term (tag or category)
	 *
	 * @param string $type Term term (post_tag or category).
	 * @param string $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	private static function get_terms( $type, $value ) {
		$results = [];
		$terms = \get_terms( [
			'taxonomy' => $type,
			'hide_empty' => false,
			'number' => Source::AUTOCOMPLETE_LIMIT,
			'search' => $value,
		] );

		if ( ! is_array( $terms ) ) {
			return [];
		}

		foreach ( $terms as $term ) {
			if ( is_object( $term ) ) {
				$results[] = (object) [
					'id' => $term->term_id,
					'value' => $term->name,
				];
			}
		}

		return $results;
	}

	/**
	 * Autocomplete a category
	 *
	 * @param string $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_category( $value ) {
		return self::get_terms( 'category', $value );
	}

	/**
	 * Autocomplete a tag
	 *
	 * @param string $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_tag( $value ) {
		return self::get_terms( 'post_tag', $value );
	}

	/**
	 * Autocomplete meta data
	 *
	 * @param Sql\Value $table Meta table.
	 * @param string    $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_meta( Sql\Value $table, $value ) {
		global $wpdb;

		$table = $wpdb->prefix . $table->get_value();

		// phpcs:ignore
		return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_key as id,meta_key as value FROM {$table} WHERE meta_key LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Source::AUTOCOMPLETE_LIMIT ) );
	}

	/**
	 * Autocomplete a comment
	 *
	 * @param string $value Value.
	 * @return list<object{id: string, value: string}>
	 */
	public static function get_comment( $value ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT comment_ID as id,comment_content as value FROM {$wpdb->comments} WHERE comment_content LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Source::AUTOCOMPLETE_LIMIT ) );
	}
}
