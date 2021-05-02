<?php

namespace SearchRegex;

class Convert_Values {
	public function convert( Schema_Column $column, $value ) {
		$func = 'get_' . $column->get_column();

		if ( method_exists( $this, $func ) ) {
			return $this->$func( $column, $value );
		}

		$func = 'get_' . $column->get_type();

		if ( method_exists( $this, $func ) ) {
			return $this->$func( $column, $value );
		}

		return $value;
	}

	public function get_post_tag( $column, $value ) {
		return $this->get_term( $column, $value );
	}

	public function get_category( $column, $value ) {
		return $this->get_term( $column, $value );
	}

	public function get_term( $column, $value ) {
		$term = get_term( $value, $column->get_column() );

		if ( $term && ! is_wp_error( $term ) ) {
			return $term->name;
		}

		return $value;
	}

	public function get_post_type( $column, $value ) {
		$names = get_post_types( [ 'name' => $value ], 'objects' );

		if ( isset( $names[ $value ] ) ) {
			return $names[ $value ]->label;
		}

		return $value;
	}

	public function get_post_author( $column, $value ) {
		return $this->get_user( $column, $value );
	}

	public function get_user( $column, $value ) {
		$user = get_userdata( intval( $value, 10 ) );

		if ( $user ) {
			return $user->display_name;
		}

		return $value;
	}

	public function get_post_title( $column, $value ) {
		if ( ! is_numeric( $value ) ) {
			return $value;
		}

		$post = get_post( $value );

		if ( $post ) {
			return $post->post_title;
		}

		return $value;
	}

	public function get_date( $column, $value ) {
		return date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), mysql2date( 'U', $value ) );
	}
}
