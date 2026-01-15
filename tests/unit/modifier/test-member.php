<?php
/**
 * Unit tests for the Modifier\Value\Member_Value class.
 *
 * @package Search_Regex
 */

use SearchRegex\Modifier;
use SearchRegex\Schema;

class ModifierMemberTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	private function getModifier( array $options ): Modifier\Value\Member_Value {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'user' ], $source );
		return new Modifier\Value\Member_Value( $options, $column );
	}

	public function testDefault() {
		$modifier = $this->getModifier( [] );
		$expected = [
			'operation' => 'replace',
			'column' => 'user',
			'source' => 'posts',
			'values' => [],
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testReplaceOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'replace', 'values' => [ 1, 2, 3 ] ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'replace', $json['operation'] );
		$this->assertEquals( [ 1, 2, 3 ], $json['values'] );
	}

	public function testIncludeOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'include', 'values' => [ 1, 2 ] ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'include', $json['operation'] );
		$this->assertEquals( [ 1, 2 ], $json['values'] );
	}

	public function testExcludeOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'exclude', 'values' => [ 1, 2 ] ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'exclude', $json['operation'] );
		$this->assertEquals( [ 1, 2 ], $json['values'] );
	}

	public function testBadOperationDefaultsToReplace() {
		$modifier = $this->getModifier( [ 'operation' => 'invalid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'replace', $json['operation'] );
	}

	public function testBadValuesIgnored() {
		$modifier = $this->getModifier( [ 'values' => 1 ] );
		$json = $modifier->to_json();

		$this->assertEquals( [], $json['values'] );
	}

	public function testEmptyValues() {
		$modifier = $this->getModifier( [ 'values' => [] ] );
		$json = $modifier->to_json();

		$this->assertEquals( [], $json['values'] );
	}

	public function testStringValuesInArray() {
		$modifier = $this->getModifier( [ 'values' => [ 'a', 'b' ] ] );
		$json = $modifier->to_json();

		$this->assertEquals( [ 'a', 'b' ], $json['values'] );
	}
}
