<?php

use SearchRegex\Match;
use SearchRegex\Context\Type;

class SearchApiTest extends SearchRegex_Api_Test {
	private function get_endpoints() {
		$search_args = [
			'searchPhrase' => 'cat',
			'source' => [ 'posts' ],
		];

		return [
			[ 'search', 'POST', $search_args ],
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
