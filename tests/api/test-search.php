<?php

use SearchRegex\Match;
use SearchRegex\Match_Context;
use SearchRegex\Search_Flags;

class SearchApiTest extends Redirection_Api_Test {
	private function get_endpoints() {
		$search_args = [
			'searchPhrase' => 'cat',
			'source' => [ 'posts' ],
		];

		return [
			[ 'search', 'GET', $search_args ],
			// [ 'source/post/1', 'GET', [] ],
			// [ 'source/post/1', 'POST', [] ],
			// [ 'source/post/1/delete', 'POST', [] ],
		];
	}

	public function testNoPermission() {
		$this->setUnauthorised();

		// None of these should work
		$this->check_endpoints( $this->get_endpoints() );
	}

	public function testAdminPermission() {
		// All of these should work
		$this->check_endpoints( $this->get_endpoints(), $this->get_endpoints() );
	}
}
