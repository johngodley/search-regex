<?php
/**
 * Unit tests for the Modifier\Value\String_Value class.
 *
 * @package Search_Regex
 */

use SearchRegex\Modifier;
use SearchRegex\Schema;

class ModifierStringTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	private function getModifier( array $options ): Modifier\Value\String_Value {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date' ], $source );
		return new Modifier\Value\String_Value( $options, $column );
	}

	public function testDefault() {
		$modifier = $this->getModifier( [] );
		$expected = [
			'operation' => 'set',
			'column' => 'date',
			'source' => 'posts',
			'searchValue' => null,
			'replaceValue' => null,
			'searchFlags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSetOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'set', 'replaceValue' => 'cats' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
		$this->assertEquals( 'cats', $json['replaceValue'] );
	}

	public function testReplaceOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cats' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'replace', $json['operation'] );
		$this->assertEquals( 'test', $json['searchValue'] );
		$this->assertEquals( 'cats', $json['replaceValue'] );
	}

	public function testSearchFlags() {
		$modifier = $this->getModifier( [ 'searchFlags' => [ 'case', 'regex' ] ] );
		$json = $modifier->to_json();

		$this->assertEquals( [ 'case', 'regex' ], $json['searchFlags'] );
	}

	public function testEmptySearchFlags() {
		$modifier = $this->getModifier( [ 'searchFlags' => [] ] );
		$json = $modifier->to_json();

		$this->assertEquals( [], $json['searchFlags'] );
	}

	public function testBadOperationDefaultsToSet() {
		$modifier = $this->getModifier( [ 'operation' => 'invalid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
	}

	public function testBadReplaceValueIgnored() {
		$modifier = $this->getModifier( [ 'replaceValue' => [ 'array' ] ] );
		$json = $modifier->to_json();

		$this->assertNull( $json['replaceValue'] );
	}

	public function testBadSearchValueIgnored() {
		$modifier = $this->getModifier( [ 'searchValue' => [ 'array' ] ] );
		$json = $modifier->to_json();

		$this->assertNull( $json['searchValue'] );
	}

	public function testStringSearchFlagsConvertedToArray() {
		// Non-array values get wrapped in an array
		$modifier = $this->getModifier( [ 'searchFlags' => 'case' ] );
		$json = $modifier->to_json();

		$this->assertEquals( [ 'case' ], $json['searchFlags'] );
	}

	public function testInvalidSearchFlagsConvertedToArray() {
		// Invalid strings still get wrapped in an array but filtered by Flags class
		$modifier = $this->getModifier( [ 'searchFlags' => 'not-valid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( [], $json['searchFlags'] );
	}
}
