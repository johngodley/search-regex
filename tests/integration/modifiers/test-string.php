<?php

use SearchRegex\Schema;
use SearchRegex\Modifier;
use SearchRegex\Context;
use SearchRegex\Source;
use SearchRegex\Search;

class Modifier_String_Test extends SearchRegex_Api_Test {
	private function get_modifier( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date' ], $source );
		return new Modifier\Value\String_Value( $options, $column );
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
			'column' => 'date',
			'source' => 'posts',
			'searchValue' => null,
			'replaceValue' => null,
			'searchFlags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testSetBad() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'replaceValue' => [ 'thing' ] ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testSet() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'replaceValue' => 'cats' ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'cats', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}

	public function testSetSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'set', 'replaceValue' => 'this is a test' ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a test', $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testSearch() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cats' ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a cats', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}

	public function testSearchCase() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cats', 'searchFlags' => [] ] );
		$context = $this->perform( $modifier, 'this is a TEST' );

		$this->assertInstanceOf( Context\Type\Value::class, $context );

		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cats', 'searchFlags' => [ 'case' ] ] );
		$context = $this->perform( $modifier, 'this is a cats' );

		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testSearchSerialized() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cats' ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a cats', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}

	public function testSearchRegex() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat' ] );
		$context = $this->perform( $modifier, serialize( [ 'this is a test' ] ) );

		$this->assertEquals( serialize( [ 'this is a cat' ] ), $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}

	public function testSearchRegexPos() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat', 'posId' => 15 ] );
		$context = $this->perform( $modifier, 'test this is a test' );

		$this->assertEquals( 'test this is a cat', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}
}
