<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Join on meta table
 */
class Meta extends Join {
	/**
	 * Source
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Meta table name
	 *
	 * @var string
	 */
	private $meta_table;

	/**
	 * Source table name
	 *
	 * @var string
	 */
	private $source_table;

	/**
	 * ID column to join on
	 *
	 * @var string
	 */
	private $join_id;

	/**
	 * Table to join on
	 *
	 * @var string
	 */
	private $table_id;

	/**
	 * Column to group by
	 *
	 * @var string
	 */
	private $group_id;

	/**
	 * ID in meta table to join on
	 *
	 * @var string
	 */
	private $meta_id;

	/**
	 * Constructor
	 *
	 * @param string $meta_type Meta type.
	 * @param string $source Source.
	 */
	public function __construct( $meta_type, $source ) {
		global $wpdb;

		$this->column = $meta_type;
		$this->source = $source;
		$this->source_table = '';
		$this->meta_id = 'meta_id';
		$this->group_id = '';
		$this->table_id = '';
		$this->meta_table = '';
		$this->table_id = 'ID';
		$this->join_id = '';

		if ( $source === 'postmeta' ) {
			$this->meta_table = $wpdb->postmeta;
			$this->source_table = $wpdb->posts;
			$this->join_id = 'post_id';
			$this->group_id = $wpdb->posts . '.ID';
		} elseif ( $source === 'commentmeta' ) {
			$this->meta_table = $wpdb->commentmeta;
			$this->source_table = $wpdb->comments;
			$this->join_id = 'comment_id';
			$this->group_id = $wpdb->comments . '.comment_ID';
			$this->table_id = 'comment_id';
		} elseif ( $source === 'usermeta' ) {
			$this->meta_table = $wpdb->usermeta;
			$this->source_table = $wpdb->users;
			$this->join_id = 'user_id';
			$this->group_id = $wpdb->users . '.ID';
			$this->meta_id = 'umeta_id';
		}
	}

	public function get_select() {
		return new Sql\Select\Select( Sql\Value::table( $this->meta_table ), Sql\Value::column( '0' ), Sql\Value::column( 'meta_id' ) );
	}

	public function get_group() {
		return new Sql\Group( Sql\Value::column( $this->group_id ) );
	}

	public function get_from() {
		if ( $this->is_matching ) {
			return new Sql\From( Sql\Value::safe_raw( sprintf( 'LEFT JOIN %s AS meta ON %s.%s = meta.%s', $this->meta_table, $this->source_table, $this->table_id, $this->join_id ) ) );
		}

		return false;
	}

	public function get_join_column() {
		return 'meta.' . $this->column;
	}

	public function get_join_value( $meta_id ) {
		global $wpdb;

		if ( $this->column === 'meta_key' ) {
			// phpcs:ignore
			return $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM {$this->meta_table} WHERE {$this->meta_id}=%d", $meta_id ) );
		}

		// phpcs:ignore
		return $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$this->meta_table} WHERE {$this->meta_id}=%d", $meta_id ) );
	}

	/**
	 * Get meta values
	 *
	 * @param array $values Meta ID values.
	 * @return array
	 */
	public function get_values( array $values ) {
		global $wpdb;

		$in = new Sql\Where\Where_In( new Sql\Select\Select( Sql\Value::table( $this->meta_table ), Sql\Value::column( $this->column ) ), 'IN', $values );

		// phpcs:ignore
		return $wpdb->get_results( "SELECT meta_key,meta_value FROM {$this->meta_table} WHERE {$this->meta_id} IN ". $in->get_value() );
	}

	public function get_column() {
		return 'meta';
	}

	/**
	 * Get all the values for this join
	 *
	 * @param integer $row_id Row ID.
	 * @return array
	 */
	public function get_all_values( $row_id ) {
		global $wpdb;

		// phpcs:ignore
		return $wpdb->get_col( $wpdb->prepare( "SELECT {$this->meta_id} FROM {$this->get_table()} WHERE {$this->join_id} = %d", $row_id ) );
	}

	public function get_table() {
		return $this->meta_table;
	}
}
