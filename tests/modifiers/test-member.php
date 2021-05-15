<?php

use SearchRegex\Schema_Column;
use SearchRegex\Schema_Source;
use SearchRegex\Modify_Member;
use SearchRegex\Match_Context_Replace;
use SearchRegex\Match_Context_Add;
use SearchRegex\Match_Context_Value;
use SearchRegex\Match_Context_Delete;
use SearchRegex\Source_Manager;
use SearchRegex\Match_Column;

class Modifier_Member_Test extends SearchRegex_Api_Test {
	private function get_modifier( $options ) {
		$source = new Schema_Source( [ 'type' => 'posts' ] );
		$column = new Schema_Column( [ 'column' => 'user' ], $source );
		return new Modify_Member( $options, $column );
	}

	private function perform( $modifier, array $value ) {
		$source = Source_Manager::get( [ 'posts' ], [] );
		$contexts = array_map( function( $item ) {
			return new Match_Context_Value( $item );
		}, $value );
		$column = new Match_Column( 1, 1, $contexts, [] );

		$results = $modifier->perform( 1, $value, $source[0], $column, [] );

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
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
	}

	public function testReplaceAddSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 19, 20 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
	}

	public function testReplaceAdd() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 1, 2, 3 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 3, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Add::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Replace::class, $contexts[1] );
		$this->assertInstanceOf( Match_Context_Replace::class, $contexts[2] );
	}

	public function testReplaceDelete() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'values' => [ 1 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Delete::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Replace::class, $contexts[1] );
	}

	public function testInclude() {
		$modifier = $this->get_modifier( [ 'operation' => 'include', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 4, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
		$this->assertInstanceOf( Match_Context_Add::class, $contexts[2] );
		$this->assertInstanceOf( Match_Context_Add::class, $contexts[3] );
	}

	public function testIncludeSame() {
		$modifier = $this->get_modifier( [ 'operation' => 'include', 'values' => [ 19, 20 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
	}

	public function testExclude() {
		$modifier = $this->get_modifier( [ 'operation' => 'exclude', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 1, 2, 19, 20 ] );

		$this->assertEquals( 4, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
		$this->assertInstanceOf( Match_Context_Delete::class, $contexts[2] );
		$this->assertInstanceOf( Match_Context_Delete::class, $contexts[3] );
	}

	public function testExcludeNone() {
		$modifier = $this->get_modifier( [ 'operation' => 'exclude', 'values' => [ 1, 2 ] ] );
		$contexts = $this->perform( $modifier, [ 19, 20 ] );

		$this->assertEquals( 2, count( $contexts ) );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[0] );
		$this->assertInstanceOf( Match_Context_Value::class, $contexts[1] );
	}
}
