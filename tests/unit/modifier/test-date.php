<?php
/**
 * Unit tests for the Modifier\Value\Date_Value class.
 *
 * @package Search_Regex
 */

use SearchRegex\Modifier;
use SearchRegex\Schema;

class ModifierDateTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	private function getModifier( array $options ): Modifier\Value\Date_Value {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date' ], $source );
		return new Modifier\Value\Date_Value( $options, $column );
	}

	public function testDefault() {
		$modifier = $this->getModifier( [] );
		$expected = [
			'operation' => 'set',
			'value' => null,
			'unit' => 'hour',
			'column' => 'date',
			'source' => 'posts',
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSetOperation() {
		$modifier = $this->getModifier( [ 'operation' => 'set', 'value' => '2020-01-01 01:01:01' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
		// Date strings are converted to timestamps when operation is 'set'
		$this->assertEquals( strtotime( '2020-01-01 01:01:01' ), $json['value'] );
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

	public function testUnitWithIncrement() {
		// Unit is only applied when operation is not 'set'
		$modifier = $this->getModifier( [ 'operation' => 'increment', 'unit' => 'second' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'second', $json['unit'] );
	}

	public function testUnitWithDecrement() {
		$modifier = $this->getModifier( [ 'operation' => 'decrement', 'unit' => 'minute' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'minute', $json['unit'] );
	}

	public function testUnitIgnoredWithSet() {
		// Unit is ignored when operation is 'set'
		$modifier = $this->getModifier( [ 'operation' => 'set', 'unit' => 'second' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'hour', $json['unit'] );
	}

	public function testAllUnits() {
		$units = [ 'second', 'minute', 'hour', 'day', 'week', 'month', 'year' ];
		foreach ( $units as $unit ) {
			$modifier = $this->getModifier( [ 'operation' => 'increment', 'unit' => $unit ] );
			$json = $modifier->to_json();
			$this->assertEquals( $unit, $json['unit'] );
		}
	}

	public function testBadOperationDefaultsToSet() {
		$modifier = $this->getModifier( [ 'operation' => 'invalid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'set', $json['operation'] );
	}

	public function testBadUnitDefaultsToHour() {
		$modifier = $this->getModifier( [ 'operation' => 'increment', 'unit' => 'invalid' ] );
		$json = $modifier->to_json();

		$this->assertEquals( 'hour', $json['unit'] );
	}
}
