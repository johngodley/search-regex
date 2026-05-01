<?php

use PHPUnit\Framework\TestCase as BaseTestCase;
use Brain\Monkey;

abstract class TestCase extends BaseTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Define WordPress function stubs that are needed for unit tests.
		Monkey\Functions\stubs( [
			'is_serialized' => [ self::class, 'is_serialized' ],
			'wp_kses' => function ( $string, $allowed_html = 'post' ) {
				return strip_tags( $string );
			},
			'__' => function ( $text, $domain = 'default' ) {
				return $text;
			},
			'stripslashes_deep' => function ( $value ) {
				return is_array( $value )
					? array_map( 'stripslashes_deep', $value )
					: stripslashes( $value );
			},
			'apply_filters' => function ( $hook, $value ) {
				return $value;
			},
		] );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		$this->resetWpdb();
		parent::tearDown();
	}

	/**
	 * WordPress is_serialized() implementation.
	 *
	 * @param mixed $data The data to check.
	 * @return bool True if serialized, false otherwise.
	 */
	public static function is_serialized( $data ): bool {
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}
		if ( preg_match( '/^([adObis]):/', $data, $matches ) ) {
			switch ( $matches[1] ) {
				case 'a':
				case 'O':
				case 's':
					if ( preg_match( "/^{$matches[1]}:[0-9]+:.*[;}]\$/s", $data ) ) {
						return true;
					}
					break;
				case 'b':
				case 'i':
				case 'd':
					if ( preg_match( "/^{$matches[1]}:[0-9.E+-]+;\$/", $data ) ) {
						return true;
					}
					break;
			}
		}
		return false;
	}

	/**
	 * Set up the global $wpdb mock for SQL-related tests.
	 *
	 * @param string $column_charset Charset reported by get_col_charset() (default 'utf8mb4').
	 */
	protected function setUpWpdb( string $column_charset = 'utf8mb4' ): void {
		global $wpdb;
		$wpdb = new class( $column_charset ) {
			public $prefix = 'wp_';
			private string $column_charset;

			public function __construct( string $column_charset ) {
				$this->column_charset = $column_charset;
			}

			public function prepare( $format, ...$args ) {
				$value = $args[0] ?? '';
				if ( $format === '%d' ) {
					return (string) intval( $value );
				}
				if ( $format === '%s' ) {
					return "'" . addslashes( $value ) . "'";
				}
				return "'" . addslashes( $value ) . "'";
			}

			public function esc_like( $text ) {
				return addcslashes( $text, '_%\\' );
			}

			public function has_cap( $cap ) {
				return $cap === 'utf8mb4' && $this->column_charset === 'utf8mb4';
			}

			public function get_col_charset( $table, $column ) {
				return $this->column_charset;
			}
		};
	}

	/**
	 * Reset the global $wpdb mock.
	 */
	protected function resetWpdb(): void {
		global $wpdb;
		$wpdb = null;
	}
}

if ( ! function_exists( 'wp_parse_str' ) ) {
	function wp_parse_str( $input_string, &$result ) {
		parse_str( (string) $input_string, $result );

		return $result;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}
