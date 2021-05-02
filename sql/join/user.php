<?php

namespace SearchRegex\Sql;

class Sql_Join_User extends Sql_Join {
	private $logic;
	private $join_table;
	private $join_column;

	public function __construct( $column, $source ) {
		global $wpdb;

		$this->column = $column;
		$this->join_column = 'user_id';

		if ( $source === 'comment' ) {
			$this->join_table = $wpdb->comments;
			$this->join_column = 'user_id';
			$this->join_to = $wpdb->users;
			$this->join_to_column = 'ID';
		} elseif ( $source === 'posts' ) {
			$this->join_table = $wpdb->posts;
			$this->join_column = 'post_author';
			$this->join_to = $wpdb->users;
			$this->join_to_column = 'ID';
		} elseif ( $source === 'users' ) {
			$this->join_table = $wpdb->users;
			$this->join_to_column = 'ID';
			$this->join_to = $wpdb->users;
		} elseif ( $source === 'user-meta' ) {
			$this->join_table = $wpdb->usermeta;
			$this->join_column = 'user_id';
			$this->join_to = $wpdb->users;
			$this->join_to_column = 'ID';
		}
	}

	public function set_logic( $logic ) {
		$this->logic = $logic;
	}

	public function get_select() {
		return new Sql_Select( Sql_Value::raw( $this->join_table ), Sql_Value::column( $this->join_column ), null, true );
	}

	public function get_from() {
		global $wpdb;

		return new Sql_From( Sql_Value::raw( sprintf( "LEFT JOIN {$wpdb->users} ON {$wpdb->users}.ID=%s.%s", $this->join_table, $this->join_column ) ) );
	}

	public function get_join_column() {
		return $this->join_column;
	}

	public function get_where() {
		return new Sql_Where_Null( new Sql_Select( Sql_Value::raw( $this->join_to_column ), null, Sql_Value::raw( $this->join_to ) ), $this->logic );
	}

	public function get_join_value( $value ) {
		return "$value";
	}

	public function get_table() {
		return $this->join_table;
	}
}