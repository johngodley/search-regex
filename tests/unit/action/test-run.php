<?php
/**
 * Unit tests for the Action\Type\Run class.
 *
 * @package Search_Regex
 */

use SearchRegex\Action\Type\Run;
use SearchRegex\Schema;
use SearchRegex\Source;
use Brain\Monkey\Functions;

class RunTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	private function getSchema(): Schema\Schema {
		return new Schema\Schema( [ [ 'type' => 'posts' ] ] );
	}

	public function testValidHookNameIsPreserved() {
		Functions\expect( 'has_action' )
			->once()
			->with( 'my_custom-hook_123' )
			->andReturn( true );

		$run = new Run( [ 'hook' => 'my_custom-hook_123' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => 'my_custom-hook_123' ], $run->to_json()['actionOption'] );
	}

	public function testInvalidCharactersAreStrippedButValidOnesKept() {
		// has_action() must be called with the sanitized name, not the raw input.
		Functions\expect( 'has_action' )
			->once()
			->with( 'myhookDROPTABLEwp_posts--' )
			->andReturn( true );

		$run = new Run( [ 'hook' => "my hook'; DROP TABLE wp_posts;--" ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => 'myhookDROPTABLEwp_posts--' ], $run->to_json()['actionOption'] );
	}

	public function testHookIsValidatedAfterSanitizingNotBefore() {
		// The raw hook contains a disallowed '.' character. If has_action() were (incorrectly) called
		// with the raw value, this expectation would not match and the test would fail.
		Functions\expect( 'has_action' )
			->once()
			->with( 'wp_ajax_dosomething' )
			->andReturn( true );

		$run = new Run( [ 'hook' => 'wp_ajax_do.something' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => 'wp_ajax_dosomething' ], $run->to_json()['actionOption'] );
	}

	public function testHookThatSanitizesToEmptyStringIsNotSet() {
		Functions\expect( 'has_action' )->never();

		$run = new Run( [ 'hook' => '!!!' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => false ], $run->to_json()['actionOption'] );
	}

	public function testNonStringHookOptionIsNotSet() {
		// An array cast to string becomes the literal "Array", which would otherwise
		// pass sanitizing and could accidentally match a real registered hook name.
		Functions\expect( 'has_action' )->never();

		$run = new Run( [ 'hook' => [ 'not', 'a', 'string' ] ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => false ], $run->to_json()['actionOption'] );
	}

	public function testHookNamedZeroIsPreserved() {
		// The string "0" is falsy in PHP, so a naive `if ( $hook && ... )` check would
		// incorrectly treat this as an empty/unset hook rather than a real hook name.
		Functions\expect( 'has_action' )
			->once()
			->with( '0' )
			->andReturn( true );

		$run = new Run( [ 'hook' => '0' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => '0' ], $run->to_json()['actionOption'] );
	}

	public function testPerformFiresHookNamedZero() {
		Functions\expect( 'has_action' )->once()->with( '0' )->andReturn( true );

		$run = new Run( [ 'hook' => '0' ], $this->getSchema() );
		$run->set_save_mode( true );

		$source = $this->getSource();
		$row = [ 'ID' => 1 ];

		Functions\expect( 'do_action' )
			->once()
			->with( '0', $row, 123, $source, [] );

		$result = $run->perform( 123, $row, $source, [] );

		$this->assertEquals( [], $result );
	}

	public function testUnregisteredHookIsNotSet() {
		Functions\when( 'has_action' )->justReturn( false );

		$run = new Run( [ 'hook' => 'not_a_real_hook' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => false ], $run->to_json()['actionOption'] );
	}

	public function testMissingHookOptionIsNotSet() {
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( [], $this->getSchema() );

		$this->assertEquals( [ 'hook' => false ], $run->to_json()['actionOption'] );
	}

	public function testNonArrayOptionsIsNotSet() {
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( 'not-an-array', $this->getSchema() );

		$this->assertEquals( [ 'hook' => false ], $run->to_json()['actionOption'] );
	}

	private function getSource(): Source\Source {
		return $this->getMockBuilder( Source\Source::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testPerformFiresHookWhenInSaveMode() {
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( [ 'hook' => 'my_hook' ], $this->getSchema() );
		$run->set_save_mode( true );

		$source = $this->getSource();
		$row = [ 'ID' => 1 ];

		Functions\expect( 'do_action' )
			->once()
			->with( 'my_hook', $row, 123, $source, [] );

		$result = $run->perform( 123, $row, $source, [] );

		$this->assertEquals( [], $result );
	}

	public function testPerformDoesNotFireHookWhenNotInSaveMode() {
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( [ 'hook' => 'my_hook' ], $this->getSchema() );

		Functions\expect( 'do_action' )->never();

		$result = $run->perform( 123, [ 'ID' => 1 ], $this->getSource(), [] );

		$this->assertEquals( [], $result );
	}

	public function testPerformDoesNotFireHookWhenNoHookIsSet() {
		Functions\when( 'has_action' )->justReturn( false );

		$run = new Run( [], $this->getSchema() );
		$run->set_save_mode( true );

		Functions\expect( 'do_action' )->never();

		$result = $run->perform( 123, [ 'ID' => 1 ], $this->getSource(), [] );

		$this->assertEquals( [], $result );
	}
}
