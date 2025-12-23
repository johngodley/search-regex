<?php

use SearchRegex\Schema;
use SearchRegex\Modifier;
use SearchRegex\Context;
use SearchRegex\Source;
use SearchRegex\Search;

class Modifier_Member_Test extends SearchRegex_Api_Test {
	private function get_modifier( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'user' ], $source );

		return new Modifier\Value\Member_Value( $options, $column );
	}

	private function perform( $modifier, array $value ) {
		$source = Source\Manager::get( [ 'posts' ], [] );
		$contexts = array_map( fn($item) => new Context\Type\Value( $item ), $value );
		$column = new Search\Column( 1, 1, $contexts, [] );

		$results = $modifier->perform( 1, $value, $source[0], $column, [], true );

		return $results->get_contexts();
	}

	public function testDefault() {
		$modifier = $this->get_modifier( [] );
		$expected = [
			'operation' => 'replace',
			'column' => 'user',
			'source' => 'posts',
			'values' => [],
		];

		$this->assertEquals( $expected, $modifier->to_json() );
	}

	public function testReplaceAddBad() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => 1 ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
	}

	public function testReplaceAddSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 19, 20 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
	}

	public function testReplaceAdd() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 1, 2, 3 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 3, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Add::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Replace::class, $contexts[1] );
		$this->assertInstanceOf( Context\Type\Replace::class, $contexts[2] );
	}

	public function testReplaceDelete() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 1 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Delete::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Replace::class, $contexts[1] );
	}

	public function testInclude() {
		$modifier = $this->get_modifier( [ 'operation' => 'include', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 4, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
		$this->assertInstanceOf( Context\Type\Add::class, $contexts[2] );
		$this->assertInstanceOf( Context\Type\Add::class, $contexts[3] );
	}

	public function testIncludeSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'include', 'values' => [ 19, 20 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
	}

	public function testExclude() {
		$modifier = $this->get_modifier( [ 'operation' => 'exclude', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 1, 2, 19, 20 ] );

		$this->assertEquals( 4, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
		$this->assertInstanceOf( Context\Type\Delete::class, $contexts[2] );
		$this->assertInstanceOf( Context\Type\Delete::class, $contexts[3] );
	}

	public function testExcludeNone() {
		$modifier = $this->get_modifier( [ 'operation' => 'exclude', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[0] );
		$this->assertInstanceOf( Context\Type\Value::class, $contexts[1] );
	}
}
