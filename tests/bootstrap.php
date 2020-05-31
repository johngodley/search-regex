<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

define( 'SEARCHREGEX_TESTS', true );

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../search-regex.php';
	require dirname( __FILE__ ) . '/../search-regex-admin.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

class Redirection_Api_Test extends WP_Ajax_UnitTestCase {
	protected function callApi( $endpoint, array $params = array(), $method = 'GET' ) {
		$request = new WP_REST_Request( $method, '/search-regex/v1/' . $endpoint );

		foreach ( $params as $name => $value ) {
			$request->set_param( $name, $value );
		}

		return rest_do_request( $request );
	}

	protected function setNonce() {
		$this->_setRole( 'administrator' );
		$this->nonce = wp_create_nonce( 'wp_rest' );
	}

	protected function setUnauthorised() {
		$this->_setRole( 'anything' );
		$this->nonce = wp_create_nonce( 'wp_rest' );
	}

	protected function setEditor() {
		$this->_setRole( 'editor' );
		$this->nonce = wp_create_nonce( 'wp_rest' );
	}

	protected function add_capability( $cap ) {
		$this->cap = $cap;
		add_filter( SearchRegex_Capability::FILTER_CAPABILITY, [ $this, 'editor_cap' ], 10, 2 );
	}

	protected function clear_capability() {
		remove_filter( SearchRegex_Capability::FILTER_CAPABILITY, [ $this, 'editor_cap' ], 10, 2 );
	}

	public function editor_cap( $cap, $name ) {
		if ( $name === $this->cap ) {
			return 'editor';
		}

		return 'manage_options';
	}

	protected function check_endpoints( $endpoints, $working = [] ) {
		foreach ( $endpoints as $endpoint ) {
			$result = $this->callApi( $endpoint[0], $endpoint[2], $endpoint[1] );
			$found = false;

			foreach ( $working as $route ) {
				if ( $route[0] === $endpoint[0] && $route[1] === $endpoint[1] ) {
					$this->assertTrue( $result->status !== 403, 'Checking found ' . $endpoint[0] . ' ' . $endpoint[1] . ' - ' . $result->status );
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$this->assertEquals( 403, $result->status, 'Checking not found ' . $endpoint[0] . ' ' . $endpoint[1] . ' - ' . $result->status );
				$this->assertEquals( 'rest_forbidden', $result->data['code'] );
			}
		}
	}

	protected static function load_fixture( $filename, $limit ) {
		$handle = fopen( dirname( __FILE__ ) . '/fixtures/' . $filename , 'r' );
		if ( ! $handle ) {
			return [];
		}

		$csv = [];
		$row = 0;
		$header = [];
		while ( ( $data = fgetcsv( $handle, 10000, ',' ) ) !== false ) {
			if ( $row === 0 ) {
				$header = $data;
			} else {
				$csv[] = array_combine( $header, $data );
			}

			$row++;
			if ( $row > $limit ) {
				break;
			}
		}

		fclose( $handle );
		return $csv;
	}

	protected static function create_posts_from_csv( array $csv ) {
		foreach ( $csv as $entry ) {
			$post_id = wp_insert_post( $entry );
			if ( $post_id instanceof WP_Error ) {
				die( 'Failed to create post' );
			}
		}
	}

	protected static function create_comments_from_csv( array $csv ) {
		foreach ( $csv as $entry ) {
			$comment_id = wp_insert_comment( $entry );

			if ( $comment_id instanceof WP_Error ) {
				die( 'Failed to create comment' );
			}
		}
	}
}
