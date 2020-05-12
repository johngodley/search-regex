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
