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
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( [ 'hook' => 'my_custom-hook_123' ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => 'my_custom-hook_123' ], $run->to_json()['actionOption'] );
	}

	public function testInvalidCharactersAreStrippedButValidOnesKept() {
		Functions\when( 'has_action' )->justReturn( true );

		$run = new Run( [ 'hook' => "my hook'; DROP TABLE wp_posts;--" ], $this->getSchema() );

		$this->assertEquals( [ 'hook' => 'myhookDROPTABLEwp_posts--' ], $run->to_json()['actionOption'] );
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
