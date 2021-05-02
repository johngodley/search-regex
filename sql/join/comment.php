<?php

namespace SearchRegex\Sql;

class Sql_Join_Comment extends Sql_Join {
	private $logic;
	private $join_table;

	public function __construct( $column, $source ) {
		global $wpdb;

		if ( $source === 'comment-meta' ) {
			$this->column = 'comment_id';
			$this->join_table = $wpdb->commentmeta;
		}
	}

	public function set_logic( $logic ) {
		$this->logic = $logic;
	}

	public function get_select() {
		global $wpdb;

		return new Sql_Select( Sql_Value::table( $wpdb->comments ), Sql_Value::column( "comment_id" ), null, true );
	}

	public function get_from() {
		global $wpdb;

		return new Sql_From( Sql_Value::raw( sprintf( "LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_id=%s.%s", $this->join_table, $this->get_join_column() ) ) );
	}

	public function get_join_column() {
		return 'comment_id';
	}

	public function get_where() {
		global $wpdb;

		return new Sql_Where_Null( new Sql_Select( Sql_Value::raw( 'comment_id' ), null, Sql_Value::raw( $wpdb->comments ) ), $this->logic );
	}

	public function get_table() {
		global $wpdb;

		return $wpdb->comments;
	}

	public function get_join_value( $value ) {
		return "$value";
	}
}
