<?php

use SearchRegex\Schema;
use SearchRegex\Modifier;
use SearchRegex\Context;
use SearchRegex\Source;
use SearchRegex\Search;

class Modifier_Date_Test extends SearchRegex_Api_Test {
	private function get_modifier( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date' ], $source );
		return new Modifier\Value\Date_Value( $options, $column );
	}

	private function perform( $modifier, $value ) {
		$source = Source\Manager::get( [ 'posts' ], [] );
		$context = new Context\Type\Value( $value );
		$column = new Search\Column( 1, 1, [ $context ], [] );

		$results = $modifier->perform( 1, $value, $source[0], $column, [], true );

		return $results->get_contexts()[0];
	}

	public function testDefault() {
		$modifier = $this->get_modifier( [] );
		$expected = [
			'operation' => 'set',
			'value' => null,
			'unit' => 'hour',
			'column' => 'date',
			'source' => 'posts',
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSet() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => '2020-01-01 01:01:01' ] );
		$context = $this->perform( $modifier, '2021-01-01 01:01:01' );

		$this->assertEquals( '2020-01-01 01:01:01', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}

	// public function testSetBad() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => 'cats' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '1970-01-01 00:00:00', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );

	// 	$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => [] ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '1970-01-01 00:00:00', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testSetSame() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'set', 'value' => '2020-01-01 01:01:01' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-01-01 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Value::class, $context );
	// }

	// public function testIncrementSecond() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 5, 'unit' => 'second' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );
	// 	$json = $context->to_json();

	// 	$this->assertEquals( '2020-01-01 01:01:06', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// 	$this->assertEquals( 'January 1, 2020 1:01 am', $json['replacement_label'] );
	// }

	// public function testIncrementMinute() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 5, 'unit' => 'minute' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-01-01 01:06:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testIncrementHour() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 5, 'unit' => 'hour' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-01-01 06:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testIncrementDay() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 5, 'unit' => 'day' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-01-06 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testIncrementWeek() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 1, 'unit' => 'week' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-01-08 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testIncrementMonth() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 1, 'unit' => 'month' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2020-02-01 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testIncrementYear() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'increment', 'value' => 1, 'unit' => 'year' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2021-01-01 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }

	// public function testDecrementYear() {
	// 	$modifier = $this->get_modifier( [ 'operation' => 'decrement', 'value' => 1, 'unit' => 'year' ] );
	// 	$context = $this->perform( $modifier, '2020-01-01 01:01:01' );

	// 	$this->assertEquals( '2019-01-01 01:01:01', $context->get_replacement() );
	// 	$this->assertInstanceOf( Context\Type\Replace::class, $context );
	// }
}
