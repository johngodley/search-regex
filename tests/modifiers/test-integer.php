<?php

use SearchRegex\Schema_Column;
use SearchRegex\Schema_Source;
use SearchRegex\Modify_Integer;
use SearchRegex\Match_Context_Replace;
use SearchRegex\Match_Context_Value;
use SearchRegex\Source_Manager;
use SearchRegex\Match_Column;

class Modifier_Integer_Test extends SearchRegex_Api_Test {
	private function get_modifier( $options ) {
		$source = new Schema_Source( [ 'type' => 'posts' ] );
		$column = new Schema_Column( [ 'column' => 'date' ], $source );
		return new Modify_Integer( $options, $column );
	}

	private function perform( $modifier, $value ) {
		$source = Source_Manager::get( [ 'posts' ], [] );
		$context = new Match_Context_Value( $value );
		$column = new Match_Column( 1, 1, [ $context ], [] );

		$results = $modifier->perform( 1, $value, $source[0], $column, [] );

		return $results->get_contexts()[0];
	}

	public function testDefault() {
		$modifier = $this->get_modifier( [] );
		$expected = [
			'operation' => 'set',
			'value' => null,
			'column' => 'date',
			'source' => 'posts',
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSet() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => 100 ] );
		$context = $this->perform( $modifier, 10 );

		$this->assertEquals( 100, $context->get_replacement() );
		$this->assertInstanceOf( Match_Context_Replace::class, $context );
	}

	public function testSetBad() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => 'cats' ] );
		$context = $this->perform( $modifier, 10 );

		$this->assertEquals( 0, $context->get_replacement() );
		$this->assertInstanceOf( Match_Context_Replace::class, $context );

		$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => [] ] );
		$context = $this->perform( $modifier, 10 );

		$this->assertEquals( 0, $context->get_replacement() );
		$this->assertInstanceOf( Match_Context_Replace::class, $context );
	}

	public function testSetSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => 10 ] );
		$context = $this->perform( $modifier, 10 );

		$this->assertInstanceOf( Match_Context_Value::class, $context );
	}

	public function testIncrementSecond() {
		$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 5 ] );
		$context = $this->perform( $modifier, 10 );
		$json = $context->to_json();

		$this->assertEquals( 15, $context->get_replacement() );
		$this->assertInstanceOf( Match_Context_Replace::class, $context );
	}

	public function testDecrementYear() {
		$modifier = $this->get_modifier( [ 'operation' => 'decrement', 'value' => 5 ] );
		$context = $this->perform( $modifier, 10 );

		$this->assertEquals( 5, $context->get_replacement() );
		$this->assertInstanceOf( Match_Context_Replace::class, $context );
	}
}
