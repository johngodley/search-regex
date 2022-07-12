<?php

use SearchRegex\Search;

class SearchFlagsTest extends WP_UnitTestCase {
	public function testNoFlags() {
		$flags = new Search\Flags();

		$this->assertEquals( [], $flags->get_flags() );
		$this->assertFalse( $flags->is_regex() );
		$this->assertFalse( $flags->is_case_insensitive() );
	}

	public function testInvalidFlags() {
		$flags = new Search\Flags( [ 'cat', 'dog', 'monkey' ] );

		$this->assertEquals( [], $flags->get_flags() );
		$this->assertFalse( $flags->is_regex() );
		$this->assertFalse( $flags->is_case_insensitive() );
	}

	public function testValidFlags() {
		$flags = new Search\Flags( [ 'regex', 'case' ] );

		$this->assertEquals( 2, count( $flags->get_flags() ) );
		$this->assertTrue( $flags->is_regex() );
		$this->assertTrue( $flags->is_case_insensitive() );
		$this->assertFalse( $flags->has_flag( 'cat' ) );
	}
}
