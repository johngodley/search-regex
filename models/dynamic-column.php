<?php

namespace SearchRegex;

use SearchRegex\Sql\Sql_Join_Term;

class Dynamic_Column {
	const LOOP_MAX = 10;

	private $shortcodes;
	private $old_shortcodes = [];
	private $row_id;
	private $row_value;
	private $raw;
	private $schema;
	private $level = 0;

	public function __construct() {
		add_filter( 'searchregex_text', [ $this, 'replace_text' ], 10, 5 );

		$this->shortcodes = apply_filters( 'searchregex_shortcodes', [
			'md5',
			'upper',
			'lower',
			'dashes',
			'underscores',
			'column',
			'value',
			'date',
		] );

		global $shortcode_tags;

		$this->old_shortcodes = array_merge( [], $shortcode_tags );

		remove_all_shortcodes();

		foreach ( $this->shortcodes as $code ) {
			add_shortcode( $code, [ $this, 'do_shortcode' ] );
		}
	}

	public function __destruct() {
		// Restore shortcodes
		// phpcs:ignore
		$shortcode_tags = array_merge( [], $this->old_shortcodes );
	}

	public function replace_text( $text, $row_id, $row_value, array $raw, Schema_Source $schema ) {
		// Keep track of these in the object
		$this->row_id = $row_id;
		$this->row_value = $row_value;
		$this->raw = $raw;
		$this->level = 0;
		$this->schema = $schema;

		return do_shortcode( $text );
	}

	/**
	 * Peform a shortcode
	 *
	 * @param array  $attrs Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * @return string
	 */
	public function do_shortcode( $attrs, $content, $tag ) {
		$this->level++;
		if ( $this->level > self::LOOP_MAX ) {
			return '';
		}

		switch ( $tag ) {
			case 'md5':
				return md5( do_shortcode( $content ) );

			case 'upper':
				return strtoupper( do_shortcode( $content ) );

			case 'lower':
				return strtolower( do_shortcode( $content ) );

			case 'dashes':
				return str_replace( [ '_', ' ' ], '-', do_shortcode( $content ) );

			case 'underscores':
				return str_replace( [ '-', ' ' ], '_', do_shortcode( $content ) );

			case 'date':
				return date( isset( $attrs['format'] ) ? $attrs['format'] : 'r' );

			case 'value':
				return $this->row_value;

			case 'column':
				$name = '';

				if ( isset( $attrs['name'] ) ) {
					$name = $attrs['name'];
				} elseif ( isset( $attrs[0] ) ) {
					$name = $attrs[0];
				}

				if ( $name && isset( $this->raw[ $name ] ) ) {
					$schema = $this->schema->get_column( $name );

					if ( $schema ) {
						return $this->get_schema_value( $schema, $attrs, $this->raw[ $name ] );
					}

					return $this->raw[ $name ];
				}

				$schema = $this->schema->get_column( $name );
				if ( $schema && $schema->get_join_column() ) {
					return $this->get_schema_join( $schema, $this->row_id, $attrs );
				}

				return '';
		}

		return apply_filters( 'searchregex_do_shortcode', '', $tag, $attrs, $content );
	}

	private function get_schema_join( Schema_Column $schema, $row_id, array $attrs ) {
		$format = isset( $attrs['format'] ) ? $attrs['format'] : 'label';

		if ( $schema->get_column() === 'category' || $schema->get_column() === 'post_tag' ) {
			$seperator = isset( $attrs['seperator'] ) ? $attrs['seperator'] : ', ';
			$join = new Sql_Join_Term( $schema->get_column() );

			return $join->get_value( $row_id, $format, $seperator );
		}

		return '';
	}

	private function get_schema_value( Schema_Column $schema, array $attrs, $row_value ) {
		if ( $schema->get_type() === 'date' && isset( $attrs['format'] ) ) {
			return date( $attrs['format'], mysql2date( 'U', $row_value ) );
		}

		if ( $schema->get_type() === 'member' && isset( $attrs['format'] ) && $attrs['format'] === 'label' ) {
			$option = $schema->get_option_label( $row_value );

			if ( $option ) {
				return $option;
			}
		}

		if ( $schema->get_joined_by() || $schema->get_join_column() ) {
			$convert = new Convert_Values();

			return $convert->convert( $schema, $row_value );
		}

		return $row_value;
	}
}
