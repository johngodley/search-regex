<?php

namespace SearchRegex\Action;

use SearchRegex\Sql;
use SearchRegex\Schema;
use SearchRegex\Source;

/**
 * Dynamic data in columns (via shortcodes)
 */
class Dynamic_Column {
	const LOOP_MAX = 10;

	/**
	 * Array of supported shortcodes
	 *
	 * @var string[]
	 */
	private array $shortcodes;

	/**
	 * Array of standard WP shortcodes
	 *
	 * @var string[]
	 */
	private array $old_shortcodes = [];

	/**
	 * Row ID
	 */
	private int $row_id = 0;

	/**
	 * Row value
	 */
	private string $row_value = '';

	/**
	 * Raw row data
	 *
	 * @var array<string, mixed>
	 */
	private array $raw = [];

	/**
	 * Source schema
	 */
	private ?Schema\Source $schema = null;

	/**
	 * Recursion level protection
	 */
	private int $level = 0;

	public function __construct() {
		add_filter( 'searchregex_text', [ $this, 'replace_text' ], 10, 5 );

		$this->shortcodes = apply_filters(
			'searchregex_shortcodes', [
				'md5',
				'upper',
				'lower',
				'dashes',
				'underscores',
				'column',
				'value',
				'date',
			]
		);

		global $shortcode_tags;

		$this->old_shortcodes = [ ...$shortcode_tags ];

		remove_all_shortcodes();

		foreach ( $this->shortcodes as $code ) {
			add_shortcode( $code, [ $this, 'do_shortcode' ] );
		}
	}

	public function __destruct() {
		global $shortcode_tags;

		// Restore shortcodes
		// phpcs:ignore
		$shortcode_tags = [ ...$this->old_shortcodes ];
	}

	/**
	 * Replace shortcodes in a value
	 *
	 * @param string        $text Replacement text, including shortcodes.
	 * @param int       $row_id Row ID.
	 * @param string        $row_value Row value.
	 * @param array<string, mixed>      $raw Raw row data.
	 * @param Schema\Source $schema Schema.
	 * @return string
	 */
	public function replace_text( $text, $row_id, $row_value, array $raw, Schema\Source $schema ) {
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
	 * @param array<string, mixed>|array<int, string> $attrs Shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * @return string
	 */
	public function do_shortcode( $attrs, $content, $tag ) {
		if ( $this->schema === null ) {
			return '';
		}

		$this->level++;
		if ( $this->level > self::LOOP_MAX ) {
			return '';
		}

		switch ( $tag ) {
			case 'md5':
				return md5( do_shortcode( $content ?? '' ) );

			case 'upper':
				return strtoupper( do_shortcode( $content ?? '' ) );

			case 'lower':
				return strtolower( do_shortcode( $content ?? '' ) );

			case 'dashes':
				return str_replace( [ '_', ' ' ], '-', do_shortcode( $content ?? '' ) );

			case 'underscores':
				return str_replace( [ '-', ' ' ], '_', do_shortcode( $content ?? '' ) );

			case 'date':
				return gmdate( $attrs['format'] ?? 'r' );

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

	/**
	 * Get schema join
	 *
	 * @param Schema\Column $schema Schema.
	 * @param int       $row_id Row ID.
	 * @param array<string, mixed>         $attrs Shortcode attributes.
	 * @return string
	 */
	private function get_schema_join( Schema\Column $schema, $row_id, array $attrs ) {
		$format = $attrs['format'] ?? 'label';

		if ( $schema->get_column() === 'category' || $schema->get_column() === 'post_tag' ) {
			$seperator = $attrs['seperator'] ?? ', ';
			$join = new Sql\Join\Term( $schema->get_column() );

			return $join->get_value( $row_id, $format, $seperator );
		}

		return '';
	}

	/**
	 * Get a label from a schema column
	 *
	 * @param Schema\Column $schema Schema.
	 * @param array<string, mixed>         $attrs Shortcode attributes.
	 * @param string        $row_value Row value.
	 * @return string
	 */
	private function get_schema_value( Schema\Column $schema, array $attrs, $row_value ) {
		if ( $schema->get_type() === 'date' && isset( $attrs['format'] ) ) {
			return gmdate( $attrs['format'], intval( mysql2date( 'U', $row_value ), 10 ) );
		}

		if ( $schema->get_type() === 'member' && isset( $attrs['format'] ) && $attrs['format'] === 'label' ) {
			$option = $schema->get_option_label( $row_value );

			if ( $option ) {
				return $option;
			}
		}

		if ( $schema->get_joined_by() !== null || $schema->get_join_column() !== null ) {
			$convert = new Source\Convert_Values();

			return $convert->convert( $schema, $row_value );
		}

		return $row_value;
	}
}
