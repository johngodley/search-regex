<?php

use SearchRegex\Source_Flags;

class SourceFlagsTest extends WP_UnitTestCase {
	public function testNoFlags() {
		$flags = new Source_Flags();

		$this->assertEquals( [], $flags->get_flags() );
	}

	public function testInvalidFlags() {
		$flags = new Source_Flags( [ 'cat', 'dog', 'monkey' ] );
		$flags->set_allowed_flags( [ 'elephant' ] );

		$this->assertEquals( [], $flags->get_flags() );
		$this->assertFalse( $flags->has_flag( 'cat' ) );
		$this->assertFalse( $flags->has_flag( 'monkey' ) );
	}

	public function testValidFlags() {
		$flags = new Source_Flags( [ 'cat', 'dog', 'monkey' ] );
		$flags->set_allowed_flags( [ 'cat', 'dog', 'monkey' ] );

		$this->assertEquals( 3, count( $flags->get_flags() ) );
		$this->assertTrue( $flags->has_flag( 'cat' ) );
		$this->assertTrue( $flags->has_flag( 'monkey' ) );
		$this->assertFalse( $flags->has_flag( 'giraffe' ) );
	}
}
