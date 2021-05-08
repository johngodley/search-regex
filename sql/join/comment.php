<?php

namespace SearchRegex\Sql;

use SearchRegex\Search_Source;

/**
 * Join on comments table
 */
class Sql_Join_Comment extends Sql_Join {
	/**
	 * Join logic
	 *
	 * @var string|false
	 */
	private $logic = false;

	/**
	 * Join table
	 *
	 * @var string
	 */
	private $join_table;

	/**
	 * Constructor
	 *
	 * @param string $column Column.
	 */
	public function __construct( $column ) {
		global $wpdb;

		$this->column = 'comment_id';
		$this->join_table = $wpdb->commentmeta;
	}

	/**
	 * Set the logic for this join
	 *
	 * @param string $logic Logic.
	 * @return void
	 */
	public function set_logic( $logic ) {
		$this->logic = $logic;
	}

	public function get_select() {
		global $wpdb;

		return new Sql_Select( Sql_Value::table( $wpdb->comments ), Sql_Value::column( 'comment_id' ), null, true );
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

		if ( $this->logic ) {
			return new Sql_Where_Null( new Sql_Select( Sql_Value::table( $wpdb->comments ), Sql_Value::column( 'comment_id' ) ), $this->logic );
		}

		return false;
	}

	public function get_table() {
		global $wpdb;

		return $wpdb->comments;
	}

	public function get_join_value( $value ) {
		return "$value";
	}
}
