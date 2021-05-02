<?php

namespace SearchRegex\Sql;

class Sql_Join_Meta extends Sql_Join {
	private $source;
	private $meta_table;
	private $source_table;
	private $join_id;
	private $table_id;
	private $meta_id;
	private $get;

	public function __construct( $meta_type, $source ) {
		global $wpdb;

		$this->column = $meta_type;
		$this->source = $source;
		$this->meta_id = 'meta_id';

		if ( $source === 'postmeta' ) {
			$this->meta_table = $wpdb->postmeta;
			$this->source_table = $wpdb->posts;
			$this->join_id = 'post_id';
			$this->group_id = $wpdb->posts . '.ID';
			$this->table_id = 'ID';
			$this->get = 'post';
		} elseif ( $source === 'commentmeta' ) {
			$this->meta_table = $wpdb->commentmeta;
			$this->source_table = $wpdb->comments;
			$this->join_id = 'comment_id';
			$this->group_id = $wpdb->comments . '.comment_ID';
			$this->table_id = 'comment_id';
			$this->get = 'comment';
		} elseif ( $source === 'usermeta' ) {
			$this->meta_table = $wpdb->usermeta;
			$this->source_table = $wpdb->users;
			$this->join_id = 'user_id';
			$this->group_id = $wpdb->users . '.ID';
			$this->meta_id = 'umeta_id';
			$this->table_id = 'ID';
			$this->get = 'user';
		}
	}

	public function get_select() {
		return new Sql_Select( Sql_Value::table( $this->meta_table ), Sql_Value::raw( "0" ), Sql_Value::column( 'meta_id' ) );
	}

	public function get_group() {
		return new Sql_Group( Sql_Value::raw( $this->group_id ) );
	}

	public function get_from() {
		global $wpdb;

		if ( $this->is_matching ) {
			return new Sql_From( Sql_Value::raw( sprintf( 'LEFT JOIN %s AS meta ON %s.%s = meta.%s', $this->meta_table, $this->source_table, $this->table_id, $this->join_id ) ) );
		}

		return null;
	}

	public function get_join_column() {
		return 'meta.' . $this->column;
	}

	// This is very inefficient at large scale
	public function get_join_value( $meta_id ) {
		global $wpdb;

		if ( $this->column === 'meta_key' ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM {$this->meta_table} WHERE {$this->meta_id}=%d", $meta_id ) );
		}

		return $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$this->meta_table} WHERE {$this->meta_id}=%d", $meta_id ) );
	}

	public function get_values( array $values ) {
		global $wpdb;

		$in = new Sql_Where_In( new Sql_Select( Sql_Value::table( $this->meta_table ), Sql_Value::column( $this->column ) ), 'IN', $values );

		return $wpdb->get_results( "SELECT meta_key,meta_value FROM {$this->meta_table} WHERE {$this->meta_id} IN ". $in->get_value() );
	}

	public function get_column() {
		return 'meta';
	}

	public function get_all_values( $row_id ) {
		global $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT {$this->meta_id} FROM {$this->get_table()} WHERE {$this->join_id} = %d", $row_id ) );
	}

	public function get_table() {
		return $this->meta_table;
	}
}
