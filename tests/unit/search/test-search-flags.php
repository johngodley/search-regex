<?php
/**
 * Unit tests for the Search\Flags class.
 *
 * @package Search_Regex
 */

use SearchRegex\Search;

class SearchFlagsTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/search/class-flags.php';
	}

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

	public function testCopyFlags() {
		$original = new Search\Flags( [ 'regex' ] );
		$copy = Search\Flags::copy( $original );

		$this->assertTrue( $copy->is_regex() );
		$this->assertFalse( $copy->is_case_insensitive() );
	}

	public function testSetRegex() {
		$flags = new Search\Flags();

		$this->assertFalse( $flags->is_regex() );

		$flags->set_regex();

		$this->assertTrue( $flags->is_regex() );
	}

	public function testSetRegexDoesNotDuplicate() {
		$flags = new Search\Flags( [ 'regex' ] );

		$flags->set_regex();

		$this->assertEquals( 1, count( $flags->get_flags() ) );
	}

	public function testToJson() {
		$flags = new Search\Flags( [ 'regex', 'case' ] );

		$this->assertEquals( [ 'regex', 'case' ], $flags->to_json() );
	}
}
