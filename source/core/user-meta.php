<?php

namespace SearchRegex;

use SearchRegex\Search_Source;

class Source_User_Meta extends Source_Meta {
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'usermeta';
	}

	public function get_table_id() {
		return 'umeta_id';
	}

	public function get_meta_object_id() {
		return 'user_id';
	}

	public function get_meta_table() {
		return 'user';
	}
}
