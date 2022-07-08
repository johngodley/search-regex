<?php

namespace SearchRegex\Source;

use SearchRegex\Schema;

/**
 * Convert a value to a label
 */
class Convert_Values {
	/**
	 * Perform the conversion on the column and value
	 *
	 * @param Schema\Column  $column Column.
	 * @param string|integer $value Value.
	 * @return string
	 */
	public function convert( Schema\Column $column, $value ) {
		$func = 'get_' . $column->get_column();

		if ( method_exists( $this, $func ) ) {
			return $this->$func( $column, $value );
		}

		$func = 'get_' . $column->get_type();

		if ( method_exists( $this, $func ) ) {
			return $this->$func( $column, $value );
		}

		return (string) $value;
	}

	/**
	 * Get a post tag given the ID
	 *
	 * @param Schema\Column $column Column.
	 * @param integer       $value Value.
	 * @return string
	 */
	public function get_post_tag( $column, $value ) {
		return $this->get_term( $column, $value );
	}

	/**
	 * Get a post category given the ID
	 *
	 * @param Schema\Column $column Column.
	 * @param integer       $value Value.
	 * @return string
	 */
	public function get_category( $column, $value ) {
		return $this->get_term( $column, $value );
	}

	/**
	 * Get a term name given the ID
	 *
	 * @param Schema\Column $column Column.
	 * @param integer       $value Value.
	 * @return string
	 */
	public function get_term( $column, $value ) {
		$term = get_term( $value, $column->get_column() );

		if ( $term && ! $term instanceof \WP_Error && is_object( $term ) ) {
			return $term->name;
		}

		return (string) $value;
	}

	/**
	 * Get a post type given the name
	 *
	 * @param Schema\Column $column Column.
	 * @param integer       $value Value.
	 * @return string
	 */
	public function get_post_type( $column, $value ) {
		$names = get_post_types( [ 'name' => $value ], 'objects' );

		if ( isset( $names[ $value ] ) && is_object( $names[ $value ] ) ) {
			return $names[ $value ]->label;
		}

		return (string) $value;
	}

	/**
	 * Get a user given the ID
	 *
	 * @param Schema\Column $column Column.
	 * @param string        $value Value.
	 * @return string
	 */
	public function get_post_author( $column, $value ) {
		return $this->get_user( $column, $value );
	}

	/**
	 * Get a user given the ID
	 *
	 * @param Schema\Column|string $column Column.
	 * @param string               $value Value.
	 * @return string
	 */
	public function get_user( $column, $value ) {
		$user = get_userdata( intval( $value, 10 ) );

		if ( $user ) {
			return $user->display_name;
		}

		return $value;
	}

	/**
	 * Get a post title given the ID
	 *
	 * @param Schema\Column $column Column.
	 * @param integer       $value Value.
	 * @return string
	 */
	public function get_post_title( $column, $value ) {
		$post = get_post( $value );

		if ( is_object( $post ) ) {
			return $post->post_title;
		}

		return (string) $value;
	}

	/**
	 * Get a date in user's date format
	 *
	 * @param Schema\Column $column Column.
	 * @param string        $value Value.
	 * @return string
	 */
	public function get_date( $column, $value ) {
		return date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), intval( mysql2date( 'U', $value ), 10 ) );
	}
}
