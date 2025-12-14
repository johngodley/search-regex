<?php

use SearchRegex\Match;
use SearchRegex\Context\Type;

class SourceApiTest extends SearchRegex_Api_Test {
	private function get_endpoints() {
		return [
			[ 'source', 'GET', [] ],
			[ 'source/posts/complete/post_title', 'GET', [ 'value' => 'cat' ] ],
			[ 'source/posts/row/12', 'GET', [] ],
			[ 'source/posts/row/12', 'POST', [ 'replacement' => [ 'column' => 'post_title' ] ] ],
			[ 'source/posts/row/12/delete', 'POST', [] ],
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
