<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Source_Comment_Meta extends Source_Meta {
	public function get_table_name() {
		global $wpdb;

		return $wpdb->commentmeta;
	}

	public function get_meta_object_id() {
		return 'comment_id';
	}

	public function get_meta_table() {
		return 'comment';
	}

	public function get_meta_name() {
		return __( 'Comment Meta', 'search-regex' );
	}

	public function autocomplete( $column, $value ) {
		if ( $column['column'] === $this->get_meta_object_id() ) {
			return Autocomplete::get_comment( $value );
		}

		return parent::autocomplete( $column, $value );
	}
}
