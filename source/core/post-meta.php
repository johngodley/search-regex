<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Source_Post_Meta extends Source_Meta {
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
}
