<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Join on comments table
 */
class Comment extends Join {
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

		return new Sql\Select\Select( Sql\Value::table( $wpdb->comments ), Sql\Value::column( 'comment_id' ), null, true );
	}

	public function get_from() {
		global $wpdb;

		$table = Sql\Value::table( $this->join_table );
		$column = Sql\Value::column( $this->get_join_column() );

		return new Sql\From( Sql\Value::safe_raw( sprintf( "LEFT JOIN {$wpdb->comments} ON {$wpdb->comments}.comment_id=%s.%s", $table->get_value(), $column->get_value() ) ) );
	}

	public function get_join_column() {
		return 'comment_id';
	}

	public function get_where() {
		global $wpdb;

		if ( $this->logic ) {
			return new Sql\Where\Where_Null( new Sql\Select\Select( Sql\Value::table( $wpdb->comments ), Sql\Value::column( 'comment_id' ) ), $this->logic );
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
