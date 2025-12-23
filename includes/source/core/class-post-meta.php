<?php

namespace SearchRegex\Source\Core;

use SearchRegex\Source;
use SearchRegex\Schema;
use SearchRegex\Sql;

class Post_Meta extends Meta {
	public function get_table_name() {
		global $wpdb;

		return $wpdb->postmeta;
	}

	public function get_meta_object_id() {
		return 'post_id';
	}

	public function get_meta_table() {
		return 'post';
	}

	public function get_meta_name() {
		return __( 'Post Meta', 'search-regex' );
	}

	public function autocomplete( array $column, $value ) {
		if ( $column['column'] === $this->get_meta_object_id() ) {
			return Source\Autocomplete::get_post( $value, Sql\Value::column( 'ID' ), Sql\Value::column( 'post_title' ) );
		}

		return parent::autocomplete( $column, $value );
	}

	public function convert_result_value( Schema\Column $schema, $value ) {
		if ( $schema->get_column() === 'post_id' ) {
			$post = get_post( intval( $value, 10 ) );

			if ( is_object( $post ) ) {
				return $post->post_title;
			}
		}

		return parent::convert_result_value( $schema, $value );
	}
}
