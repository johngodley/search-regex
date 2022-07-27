<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Join on the post table
 */
class Post extends Join {
	/**
	 * Join logic
	 *
	 * @var string
	 */
	private $logic;

	/**
	 * Source
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Column to join
	 *
	 * @var string
	 */
	private $join_column;

	/**
	 * Constructor
	 *
	 * @param string $column Column.
	 * @param string $source Source.
	 */
	public function __construct( $column, $source ) {
		global $wpdb;

		$this->column = $column;
		$this->source = $wpdb->postmeta;
		$this->join_column = '';
		$this->logic = '';

		if ( $source === 'post-meta' ) {
			$this->join_column = 'post_id';
		} elseif ( $source === 'comment-meta' ) {
			$this->join_column = 'comment_ID';
			$this->source = $wpdb->commentmeta;
		} elseif ( $source === 'comment' ) {
			$this->source = $wpdb->comments;
			$this->join_column = 'comment_post_ID';
		}
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
		return new Sql\Select\Select( Sql\Value::table( $this->source ), Sql\Value::column( $this->join_column ), null, true );
	}

	public function get_from() {
		global $wpdb;

		$source = Sql\Value::table( $this->source );
		$column = Sql\Value::column( $this->join_column );

		return new Sql\From( Sql\Value::safe_raw( sprintf( "LEFT JOIN {$wpdb->posts} ON {$wpdb->posts}.ID=%s.%s", $source->get_value(), $column->get_value() ) ) );
	}

	public function get_join_column() {
		return $this->join_column;
	}

	public function get_where() {
		global $wpdb;

		return new Sql\Where\Where_Null( new Sql\Select\Select( Sql\Value::table( $wpdb->posts ), Sql\Value::column( 'ID' ) ), $this->logic );
	}

	public function get_join_value( $value ) {
		return "$value";
	}

	public function get_table() {
		return $this->source;
	}
}
