<?php

namespace SearchRegex\Sql;

use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Group;
use SearchRegex\Sql\Sql_Where;

abstract class Sql_Join {
	protected $is_matching = true;
	protected $column = null;

	public static function create( $column, $source = null ) {
		if ( $column === 'category' || $column === 'post_tag' ) {
			return new Sql_Join_Term( $column, $source );
		}

		if ( $column === 'meta_key' || $column === 'meta_value' || $column === 'meta' ) {
			return new Sql_Join_Meta( $column, $source );
		}

		if ( $column === 'taxonomy' ) {
			return new Sql_Join_Taxonomy( $column, $source );
		}

		if ( $column === 'post' ) {
			return new Sql_Join_Post( $column, $source );
		}

		if ( $column === 'user' ) {
			return new Sql_Join_User( $column, $source );
		}

		if ( $column === 'comment' ) {
			return new Sql_Join_Comment( $column, $source );
		}

		return null;
	}

	public function get_from() {
		return false;
	}

	public function get_group() {
		return false;
	}

	public function get_select() {
		return false;
	}

	public function get_where() {
		return false;
	}

	public function get_column() {
		return $this->column;
	}

	public function set_non_matching() {
		$this->is_matching = false;
	}

	abstract public function get_join_column();
	abstract public function get_table();
}

require_once __DIR__ . '/meta.php';
require_once __DIR__ . '/term.php';
require_once __DIR__ . '/taxonomy.php';
require_once __DIR__ . '/post.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/comment.php';
