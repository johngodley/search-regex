<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Undocumented class
 */
class User extends Join {
	/**
	 * Join logic
	 *
	 * @var string
	 */
	private $logic = '';

	/**
	 * Join table
	 *
	 * @var string
	 */
	private $join_table = '';

	/**
	 * Join column
	 *
	 * @var string
	 */
	private $join_column;

	/**
	 * Join to
	 *
	 * @var string
	 */
	private $join_to;

	/**
	 * Join to column
	 *
	 * @var string
	 */
	private $join_to_column = '';

	/**
	 * Constructor
	 *
	 * @param string $column Column.
	 * @param string $source Source.
	 */
	public function __construct( $column, $source ) {
		global $wpdb;

		$this->column = $column;
		$this->join_column = 'user_id';
		$this->join_to_column = 'ID';
		$this->join_to = $wpdb->users;

		if ( $source === 'comment' ) {
			$this->join_table = $wpdb->comments;
			$this->join_column = 'user_id';
		} elseif ( $source === 'posts' ) {
			$this->join_table = $wpdb->posts;
			$this->join_column = 'post_author';
		} elseif ( $source === 'users' ) {
			$this->join_table = $wpdb->users;
			$this->join_to_column = 'ID';
		} elseif ( $source === 'user-meta' ) {
			$this->join_table = $wpdb->usermeta;
			$this->join_column = 'user_id';
		}
	}

	/**
	 * Set the logic
	 *
	 * @param string $logic Logic.
	 * @return void
	 */
	public function set_logic( $logic ) {
		$this->logic = $logic;
	}

	public function get_select() {
		return new Sql\Select\Select( Sql\Value::table( $this->join_table ), Sql\Value::column( $this->join_column ), null, true );
	}

	public function get_from() {
		global $wpdb;

		$table = Sql\Value::table( $this->join_table );
		$column = Sql\Value::column( $this->join_column );
		return new Sql\From( Sql\Value::safe_raw( sprintf( "LEFT JOIN {$wpdb->users} ON {$wpdb->users}.ID=%s.%s", $table->get_value(), $column->get_value() ) ) );
	}

	public function get_join_column() {
		return $this->join_column;
	}

	public function get_where() {
		return new Sql\Where\Where_Null( new Sql\Select\Select( Sql\Value::table( $this->join_to ), Sql\Value::column( $this->join_to_column ) ), $this->logic );
	}

	public function get_join_value( $value ) {
		return "$value";
	}

	public function get_table() {
		return $this->join_table;
	}
}
