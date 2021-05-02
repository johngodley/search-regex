<?php

namespace SearchRegex\Sql;

class Sql_Join_Post extends Sql_Join {
	private $logic;
	private $source;

	public function __construct( $column, $source ) {
		global $wpdb;

		$this->column = $column;

		if ( $source === 'post-meta' ) {
			$this->join_column = 'post_id';
			$this->source = $wpdb->postmeta;
			$this->join_column = 'post_id';
		} elseif ( $source === 'comment-meta' ) {
			$this->join_column = 'comment_ID';
			$this->source = $wpdb->commentmeta;
		} elseif ( $source === 'comment' ) {
			$this->source = $wpdb->comments;
			$this->join_column = 'comment_post_ID';
		}
	}

	public function set_logic( $logic ) {
		$this->logic = $logic;
	}

	public function get_select() {
		global $wpdb;

		return new Sql_Select( Sql_Value::table( $this->source ), Sql_Value::raw( $this->join_column ), null, true );
	}

	public function get_from() {
		global $wpdb;

		return new Sql_From( Sql_Value::raw( sprintf( "LEFT JOIN {$wpdb->posts} ON {$wpdb->posts}.ID=%s.%s", $this->source, $this->join_column ) ) );
	}

	public function get_join_column() {
		return $this->join_column;
	}

	public function get_where() {
		global $wpdb;

		return new Sql_Where_Null( new Sql_Select( Sql_Value::table( $wpdb->posts ), Sql_Value::raw( 'ID' ) ), $this->logic );
	}

	public function get_join_value( $value ) {
		return "$value";
	}

	public function get_table() {
		return $this->source;
	}
}
