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

	private function perform( $modifier, $value, $save_mode = true ) {
		$source = Source\Manager::get( [ 'posts' ], [] );
		$context = new Context\Type\Value( $value );
		$column = new Search\Column( 1, 1, [ $context ], [] );

		$results = $modifier->perform( 1, $value, $source[0], $column, [], $save_mode );

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

	public function testDoesNotReplaceSerializedValue() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat' ] );
		$value = serialize( [ 'this is a test' ] );
		$context = $this->perform( $modifier, $value, false );

		$this->assertEquals( $value, $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testDoesNotInjectObjectIntoSerializedValue() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'X', 'replaceValue' => 'Y' ] );
		$value = serialize( [ 'X";O:8:"stdClass":0:{}}' => 'actual' ] );
		$context = $this->perform( $modifier, $value );

		$this->assertEquals( $value, $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testDoesNotReplaceSerializedValueWithRegex() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'te.t', 'replaceValue' => 'cat', 'searchFlags' => [ 'regex' ] ] );
		$value = serialize( [ 'this is a test' ] );
		$context = $this->perform( $modifier, $value );

		$this->assertEquals( $value, $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testDoesNotReplacePositionInSerializedValue() {
		$value = serialize( [ 'this is a test' ] );
		$modifier = $this->get_modifier(
			[
				'operation' => 'replace',
				'searchValue' => 'test',
				'replaceValue' => 'cat',
				'posId' => strpos( $value, 'test' ),
			]
		);
		$context = $this->perform( $modifier, $value );

		$this->assertEquals( $value, $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	public function testReplaceWithDollarAndBackslashIsLiteral() {
		$replace = 'costs $10 at C:\\1dir \\\\ $0';
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => $replace ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a ' . $replace, $context->get_replacement() );
	}

	public function testReplacePreviewWithDollarAndBackslashIsLiteral() {
		$replace = 'costs $10 at C:\\1dir \\\\ $0';
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => $replace ] );

		$this->assertEquals( [ $replace ], $modifier->get_replace_positions( 'this is a test' ) );
	}

	public function testReplacePositionWithDollarAndBackslashIsLiteral() {
		$replace = 'costs $10 at C:\\1dir \\\\ $0';
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => $replace, 'posId' => 10 ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a ' . $replace, $context->get_replacement() );
	}

	public function testRegexReplaceStillUsesBackreferences() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => '(te)(st)', 'replaceValue' => '$2-\\1', 'searchFlags' => [ 'regex' ] ] );
		$context = $this->perform( $modifier, 'this is a test' );

		$this->assertEquals( 'this is a st-te', $context->get_replacement() );
	}

	public function testInvalidUtf8ValueIsNotEmptied() {
		$value = "this is a test \xE9";
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat' ] );
		$context = $this->perform( $modifier, $value );

		$this->assertEquals( $value, $context->get_value() );
		$this->assertInstanceOf( Context\Type\Value::class, $context );
	}

	/**
	 * @dataProvider invalidRegexProvider
	 */
	public function testInvalidRegexIsAnError( $save_mode, $pos_id ) {
		$options = [ 'operation' => 'replace', 'searchValue' => '(test', 'replaceValue' => 'cat', 'searchFlags' => [ 'regex' ] ];
		if ( $pos_id !== null ) {
			$options['posId'] = $pos_id;
		}

		$value = 'this is a test';
		$modifier = $this->get_modifier( $options );
		$source = Source\Manager::get( [ 'posts' ], [] );
		$column = new Search\Column( 1, 1, [ new Context\Type\Value( $value ) ], [] );

		$result = $modifier->perform( 1, $value, $source[0], $column, [], $save_mode );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( '(test', $result->get_error_message() );
	}

	public function invalidRegexProvider() {
		return [
			'save' => [ true, null ],
			'preview' => [ false, null ],
			'position' => [ true, 10 ],
		];
	}

	public function testUnbalancedPlainTextSearchIsNotAnError() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => '(test', 'replaceValue' => 'cat' ] );
		$context = $this->perform( $modifier, 'this is a (test' );

		$this->assertEquals( 'this is a cat', $context->get_replacement() );
	}

	public function testMarkerInContentDoesNotChangeReplacement() {
		$value = '<SEARCHREGEX>planted</SEARCHREGEX> this is a test';
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat' ] );

		$this->assertEquals( [ 'cat' ], $modifier->get_replace_positions( $value ) );
	}

	public function testMarkerInContentDoesNotChangePositionReplacement() {
		$value = '<SEARCHREGEX>planted</SEARCHREGEX> this is a test';
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat', 'posId' => strpos( $value, 'test' ) ] );
		$context = $this->perform( $modifier, $value );

		$this->assertEquals( '<SEARCHREGEX>planted</SEARCHREGEX> this is a cat', $context->get_replacement() );
	}

	public function testSearchRegexPos() {
		$modifier = $this->get_modifier( [ 'operation' => 'replace', 'searchValue' => 'test', 'replaceValue' => 'cat', 'posId' => 15 ] );
		$context = $this->perform( $modifier, 'test this is a test' );

		$this->assertEquals( 'test this is a cat', $context->get_replacement() );
		$this->assertInstanceOf( Context\Type\Replace::class, $context );
	}
}
