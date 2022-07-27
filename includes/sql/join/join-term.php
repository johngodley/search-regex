<?php

namespace SearchRegex\Sql\Join;

use SearchRegex\Sql;

/**
 * Join on terms
 */
class Term extends Join {
	/**
	 * Constructor
	 *
	 * @param string $term_type Type of taxonomy.
	 */
	public function __construct( $term_type ) {
		$this->column = $term_type;
	}

	public function get_where() {
		if ( $this->is_matching ) {
			return new Sql\Where\Where_String( new Sql\Select\Select( Sql\Value::column( 'tt' ), Sql\Value::column( 'taxonomy' ) ), '=', $this->column );
		}

		return false;
	}

	public function get_select() {
		return new Sql\Select\Select( Sql\Value::column( 'tt' ), Sql\Value::column( '0' ), Sql\Value::column( $this->column ) );
	}

	public function get_group() {
		global $wpdb;

		return new Sql\Group( Sql\Value::column( "{$wpdb->posts}.ID" ) );
	}

	public function get_from() {
		global $wpdb;

		if ( $this->is_matching ) {
			return new Sql\From( Sql\Value::safe_raw( sprintf( 'INNER JOIN %sterm_relationships AS tr ON (%s.ID = tr.object_id) INNER JOIN %sterm_taxonomy AS tt ON tt.term_taxonomy_id=tr.term_taxonomy_id', $wpdb->prefix, $wpdb->posts, $wpdb->prefix ) ) );
		}

		return false;
	}

	public function get_join_column() {
		return 'tr.term_taxonomy_id';
	}

	public function get_join_value( $value ) {
		$term = get_term( intval( $value, 10 ) );
		if ( $term instanceof \WP_Error ) {
			return "$value";
		}

		if ( is_object( $term ) && $term->taxonomy === $this->column ) {
			return $term->name;
		}

		return "$value";
	}

	/**
	 * Get all term values
	 *
	 * @param integer $row_id Row ID.
	 * @return integer[]
	 */
	public function get_all_values( $row_id ) {
		return wp_get_post_terms( $row_id, $this->column, [ 'fields' => 'ids' ] );
	}

	public function get_table() {
		return '';
	}

	/**
	 * Get the value
	 *
	 * @param integer $row_id Row ID.
	 * @param string  $type Term type.
	 * @param string  $seperator How to seperate the terms.
	 * @return string
	 */
	public function get_value( $row_id, $type, $seperator ) {
		$terms = wp_get_post_terms( $row_id, $this->column, [ 'fields' => 'all' ] );
		if ( $terms instanceof \WP_Error ) {
			return '';
		}

		$group = array_map( function( $term ) use ( $type ) {
			if ( $type === 'slug' ) {
				return $term->slug;
			}

			if ( $type === 'url' ) {
				return get_term_link( $term );
			}

			if ( $type === 'link' ) {
				$link = get_term_link( $term );
				if ( $link instanceof \WP_Error ) {
					return '';
				}

				return '<a href="' . esc_url( $link ) . '">' . $term->name . '</a>';
			}

			if ( $type === 'description' ) {
				return $term->description;
			}

			return $term->name;
		}, $terms );

		return implode( $seperator, $group );
	}
}
