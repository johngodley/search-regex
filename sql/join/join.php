<?php

namespace SearchRegex\Sql;

use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Group;
use SearchRegex\Sql\Sql_Where;

/**
 * SQL join
 */
abstract class Sql_Join {
	/**
	 * Does this require matching?
	 *
	 * @var boolean
	 */
	protected $is_matching = true;

	/**
	 * Column to join on
	 *
	 * @var string
	 */
	protected $column;

	/**
	 * Create a Sql_Join object
	 *
	 * @param string $column Column to join on.
	 * @param string $source Source.
	 * @return Sql_Join|null
	 */
	public static function create( $column, $source ) {
		if ( $column === 'category' || $column === 'post_tag' ) {
			return new Sql_Join_Term( $column );
		}

		if ( $column === 'meta_key' || $column === 'meta_value' || $column === 'meta' ) {
			return new Sql_Join_Meta( $column, $source );
		}

		if ( $column === 'taxonomy' ) {
			return new Sql_Join_Taxonomy( $column );
		}

		if ( $column === 'post' ) {
			return new Sql_Join_Post( $column, $source );
		}

		if ( $column === 'user' ) {
			return new Sql_Join_User( $column, $source );
		}

		if ( $column === 'comment' ) {
			return new Sql_Join_Comment( $column );
		}

		return null;
	}

	/**
	 * Get SQL for join FROM, or false if no FROM
	 *
	 * @return Sql_From|false
	 */
	public function get_from() {
		return false;
	}

	/**
	 * Get SQL for join GROUP, or false if no GROUP
	 *
	 * @return Sql_Group|false
	 */
	public function get_group() {
		return false;
	}

	/**
	 * Get SQL for join SELECT, or false if no SELECT
	 *
	 * @return Sql_Select|false
	 */
	public function get_select() {
		return false;
	}

	/**
	 * Get SQL for join WHERE, or false if no WHERE
	 *
	 * @return Sql_Where|false
	 */
	public function get_where() {
		return false;
	}

	/**
	 * Get join column
	 *
	 * @return string
	 */
	public function get_column() {
		return $this->column;
	}

	/**
	 * Set this join as non-matching
	 *
	 * @return void
	 */
	public function set_non_matching() {
		$this->is_matching = false;
	}

	/**
	 * Get the column in the joined table
	 *
	 * @return string
	 */
	abstract public function get_join_column();

	/**
	 * Get the joined value
	 *
	 * @param string $value Value.
	 * @return string
	 */
	abstract public function get_join_value( $value );

	/**
	 * Get table we are joining on
	 *
	 * @return string
	 */
	abstract public function get_table();
}
