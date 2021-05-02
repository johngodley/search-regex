<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Autocomplete {
	public static function get_user( $value ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID as id,display_name as value FROM {$wpdb->users} WHERE display_name LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Search_Source::AUTOCOMPLETE_LIMIT ) );
	}

	public static function get_post( $value, $id_column, $search_column ) {
		global $wpdb;

		$type_sql = "AND post_status != 'inherit'";  // Ignore attachments

		return $wpdb->get_results( $wpdb->prepare( "SELECT {$id_column} as id,post_title as value FROM {$wpdb->posts} WHERE {$search_column} LIKE %s {$type_sql} LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Search_Source::AUTOCOMPLETE_LIMIT ) );
	}

	private static function get_terms( $type, $value ) {
		$results = [];
		$terms = get_terms( [ 'taxonomy' => $type, 'hide_empty' => false, 'number' => Search_Source::AUTOCOMPLETE_LIMIT, 'search' => $value ] );

		foreach ( $terms as $term ) {
			$results[] = (object)[ 'id' => $term->term_id, 'value' => $term->name ];
		}

		return $results;
	}

	public static function get_category( $value ) {
		return self::get_terms( 'category', $value );
	}

	public static function get_tag( $value ) {
		return self::get_terms( 'post_tag', $value );
	}

	public static function get_meta( $table, $value ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_key as id,meta_key as value FROM {$wpdb->prefix}{$table} WHERE meta_key LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Search_Source::AUTOCOMPLETE_LIMIT ) );
	}

	public static function get_comment( $value ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT comment_ID as id,comment_content as value FROM {$wpdb->comments} WHERE comment_content LIKE %s LIMIT %d", '%' . $wpdb->esc_like( $value ) . '%', Search_Source::AUTOCOMPLETE_LIMIT ) );
	}
}
