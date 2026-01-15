<?php

use PHPUnit\Framework\TestCase as BaseTestCase;
use Brain\Monkey;

abstract class TestCase extends BaseTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Define WordPress function stubs that are needed for unit tests.
		Monkey\Functions\stubs( [
			'is_serialized' => function ( $data ) {
				if ( ! is_string( $data ) ) {
					return false;
				}
				$data = trim( $data );
				if ( 'N;' === $data ) {
					return true;
				}
				if ( strlen( $data ) < 4 ) {
					return false;
				}
				if ( ':' !== $data[1] ) {
					return false;
				}
				$lastc = substr( $data, -1 );
				if ( ';' !== $lastc && '}' !== $lastc ) {
					return false;
				}
				$token = $data[0];
				switch ( $token ) {
					case 's':
						return ( '"' === substr( $data, -2, 1 ) );
					case 'a':
					case 'O':
						return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
					case 'b':
					case 'i':
					case 'd':
						return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$/", $data );
				}
				return false;
			},
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
		parent::tearDown();
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
