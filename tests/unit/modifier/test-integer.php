<?php
/**
 * Unit tests for the Modifier\Value\Integer_Value class.
 *
 * @package Search_Regex
 */

use SearchRegex\Modifier;
use SearchRegex\Schema;

class ModifierIntegerTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	private function getModifier( array $options ): Modifier\Value\Integer_Value {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date' ], $source );
		return new Modifier\Value\Integer_Value( $options, $column );
	}

	public function testDefault() {
		$modifier = $this->getModifier( [] );
		$expected = [
			'operation' => 'set',
			'value' => null,
			'column' => 'date',
			'source' => 'posts',
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSetOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'set', 'value' => 100 ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
		$this->assertEquals( 100, $json['value'] );
	}

	public function testIncrementOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'increment', 'value' => 5 ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'increment', $json['operation'] );
		$this->assertEquals( 5, $json['value'] );
	}

	public function testDecrementOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'decrement', 'value' => 10 ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'decrement', $json['operation'] );
		$this->assertEquals( 10, $json['value'] );
	}

	public function testBadOperationDefaultsToSet() {
		$modifier = $this->getModifier( [ 'operation' => 'invalid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
	}

	public function testStringValueConvertedToInt() {
		$modifier = $this->getModifier( [ 'value' => '42' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 42, $json['value'] );
	}

	public function testBadValueConvertedToZero() {
		$modifier = $this->getModifier( [ 'value' => 'cats' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 0, $json['value'] );
	}

	public function testArrayValueConvertedToZero() {
		$modifier = $this->getModifier( [ 'value' => [] ] );
		$json = $modifier->to_json();

		$this->assertEquals( 0, $json['value'] );
	}

	public function testNegativeValue() {
		$modifier = $this->getModifier( [ 'value' => -5 ] );
		$json = $modifier->to_json();

		$this->assertEquals( -5, $json['value'] );
	}
}
