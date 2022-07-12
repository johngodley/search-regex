<?php

use SearchRegex\Context;
use SearchRegex\Search;

class ContextTest extends WP_UnitTestCase {
	public function testContextValue() {
		$context = new Context\Type\Value( 'value', 'label' );
		$json = [
			'value' => 'value',
			'value_type' => 'text',
			'value_label' => 'label',
			'value_length' => 5,
			'context_id' => 0,
			'type' => 'value',
		];

		$this->assertEquals( $json, $context->to_json() );
		$this->assertFalse( $context->is_matched() );
		$this->assertFalse( $context->needs_saving() );
		$this->assertEquals( 'text', $context->get_value_type() );
		$this->assertEquals( 'value', $context->get_value() );
	}

	public function testContextValueCrop() {
		$context = new Context\Type\Value( str_repeat( 'thing', 100 ) );

		$this->assertEquals( Context\Type\Value::MAX_LENGTH, strlen( $context->to_json()['value'] ) );
	}

	public function testContextEquals() {
		$context = new Context\Type\Value( 'value', 'label' );
		$context2 = new Context\Type\Value( 'value', 'label' );
		$context3 = new Context\Type\Value( 'value2', 'label2' );

		$this->assertTrue( $context->is_equal( $context2 ) );
		$this->assertFalse( $context->is_equal( $context3 ) );
	}

	public function testContextAdd() {
		$context = new Context\Type\Add( 'value', 'label' );

		$this->assertTrue( $context->is_matched() );
		$this->assertTrue( $context->needs_saving() );
	}

	public function testContextDelete() {
		$context = new Context\Type\Delete( 'value', 'label' );

		$this->assertTrue( $context->is_matched() );
		$this->assertTrue( $context->needs_saving() );
	}

	public function testContextMatched() {
		$context = new Context\Type\Delete( 'value', 'label' );

		$this->assertTrue( $context->is_matched() );
		$this->assertTrue( $context->needs_saving() );
	}

	public function testContextReplace() {
		$context = new Context\Type\Replace( 'value', 'label' );
		$context->set_replacement( 'replace', 'replace_label' );

		$json = [
			'value' => 'value',
			'value_type' => 'text',
			'value_label' => 'label',
			'value_length' => 5,
			'context_id' => 0,
			'type' => 'replace',
			'replacement' => 'replace',
			'replacement_label' => 'replace_label',
		];

		$this->assertTrue( $context->is_matched() );
		$this->assertTrue( $context->needs_saving() );
		$this->assertEquals( $json, $context->to_json() );
		$this->assertEquals( 'text', $context->get_value_type() );
		$this->assertEquals( 'value', $context->get_value() );
	}

	public function testContextReplaceEquals() {
		$context = new Context\Type\Replace( 'value', 'label' );
		$context2 = new Context\Type\Replace( 'value', 'label' );
		$context3 = new Context\Type\Replace( 'value2', 'label2' );

		$context->set_replacement( 'replace', 'replace' );
		$context2->set_replacement( 'replace', 'replace' );
		$context3->set_replacement( 'replace2', 'replace' );

		$this->assertTrue( $context->is_equal( $context2 ) );
		$this->assertFalse( $context->is_equal( $context3 ) );
	}

	public function testContextPair() {
		$key = new Context\Type\Value( 'key' );
		$value = new Context\Type\Value( 'value' );
		$context = new Context\Type\Pair( $key, $value );

		$json = [
			'value' => [
				'type' => 'value',
				'value' => 'value',
				'value_type' => 'text',
				'value_label' => 'value',
				'value_length' => 5,
			],
			'key' => [
				'type' => 'value',
				'value' => 'key',
				'value_type' => 'text',
				'value_label' => 'key',
				'value_length' => 3,
			],
			'context_id' => 0,
			'type' => 'keyvalue',
		];

		$this->assertFalse( $context->is_matched() );
		$this->assertFalse( $context->needs_saving() );
		$this->assertEquals( $json, $context->to_json() );
	}

	public function testContextPairEquals() {
		$key1 = new Context\Type\Value( 'key1' );
		$key2 = new Context\Type\Value( 'key2' );
		$value1 = new Context\Type\Value( 'value1' );
		$value2 = new Context\Type\Value( 'value2' );

		$context = new Context\Type\Pair( $key1, $value1 );
		$context2 = new Context\Type\Pair( $key1, $value1 );
		$context3 = new Context\Type\Pair( $key1, $value2 );

		$this->assertTrue( $context->is_equal( $context2 ) );
		$this->assertFalse( $context->is_equal( $context3 ) );
	}

	public function testContextString() {
		$context = new Context\Type\Text( 'value', new Search\Flags( [ 'case' ] ) );

		$this->assertTrue( $context->is_matched() );
		$this->assertTrue( $context->needs_saving() );

		$context->add_match( new Search\Text( 'full', 12 ), 'this is the full string' . str_repeat( 'filler', 100 ) );

		$json = $context->to_json();
		$this->assertEquals( 1, $json['match_count'] );
		$this->assertEquals( 12, $json['matches'][0]['pos_id'] );
		$this->assertEquals( 'full', $json['matches'][0]['match'] );
		$this->assertEquals( 76, $json['crop']['end'] );
	}

	public function testContextStringEquals() {
		$context1 = new Context\Type\Text( 'value1', new Search\Flags( [ 'case' ] ) );
		$context2 = new Context\Type\Text( 'value1', new Search\Flags( [ 'case' ] ) );
		$context3 = new Context\Type\Text( 'value2', new Search\Flags( [ 'case' ] ) );

		$this->assertTrue( $context1->is_equal( $context2 ) );
		$this->assertTrue( $context1->is_equal( $context3 ) );
	}
}
