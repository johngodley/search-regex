<?php

namespace SearchRegex\Sql;

class Sql_Join_Term extends Sql_Join {
	public function __construct( $term_type ) {
		$this->column = $term_type;
	}

	public function get_where() {
		if ( $this->is_matching ) {
			return new Sql_Where_String( new Sql_Select( Sql_Value::column( 'tt' ), Sql_Value::raw( 'taxonomy' ) ), '=', $this->column );
		}

		return null;
	}

	public function get_select() {
		return new Sql_Select( Sql_Value::column( 'tt' ), Sql_Value::raw( "0" ), Sql_Value::column( $this->column ) );
	}

	public function get_group() {
		global $wpdb;

		return new Sql_Group( Sql_Value::raw( "{$wpdb->posts}.ID" ) );
	}

	public function get_from() {
		global $wpdb;

		if ( $this->is_matching ) {
			return new Sql_From( Sql_Value::raw( sprintf( 'INNER JOIN %sterm_relationships AS tr ON (%s.ID = tr.object_id) INNER JOIN %sterm_taxonomy AS tt ON tt.term_taxonomy_id=tr.term_taxonomy_id', $wpdb->prefix, $wpdb->posts, $wpdb->prefix ) ) );
		}

		return null;
	}

	public function get_join_column() {
		return 'tr.term_taxonomy_id';
	}

	public function get_join_value( $value ) {
		$term = get_term( $value );

		if ( $term && ! is_wp_error( $term ) ) {
			if ( $term->taxonomy === $this->column ) {
				return $term->name;
			}

			return null;
		}

		return "$value";
	}

	public function get_all_values( $row_id ) {
		return wp_get_post_terms( $row_id, $this->column, [ 'fields' => 'ids' ] );
	}

	public function get_table() {
		return '';
	}

	public function get_value( $row_id, $type, $seperator ) {
		$terms = wp_get_post_terms( $row_id, $this->column, [ 'fields' => 'all' ] );

		$group = array_map( function( $term ) use ( $type ) {
			if ( $type === 'slug' ) {
				return $term->slug;
			}

			if ( $type === 'url' ) {
				return get_term_link( $term );
			}

			if ( $type === 'link' ) {
				return '<a href="' . esc_url( get_term_link( $term ) ) . '">' . $term->name . '</a>';
			}

			if ( $type === 'description' ) {
				return $term->description;
			}

			return $term->name;
		}, $terms );

		return implode( $seperator, $group );
	}
}
