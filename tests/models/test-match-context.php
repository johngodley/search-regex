<?php

use SearchRegex\Match;
use SearchRegex\Match_Context;
use SearchRegex\Search_Flags;

class ContextTest extends WP_UnitTestCase {
	public function testEmptyContext() {
		$match = new Match( 'match', 20, 'replace' );
		$context = new Match_Context( 1 );
		$expected = [
			'context_id' => 1,
			'context' => null,
			'matches' => [],
			'match_count' => 0,
		];

		$this->assertEquals( $expected, $context->to_json() );
		$this->assertTrue( $context->is_within_context( $match ) );
		$this->assertFalse( $context->get_match_at_position( 55 ) );
	}

	public function testContextWithMatches() {
		$context_value = 'one there is one match here and at the end is one';
		$match1 = new Match( 'one', 0, 'two' );
		$match2 = new Match( 'one', 13, 'two' );
		$match3 = new Match( 'one', 46, 'two' );
		$match4 = new Match( 'one', 46 + Match_Context::CONTEXT_RANGE + 1, 'two' );

		$context = new Match_Context( 1 );
		$context->add_match( $match1, $context_value );
		$context->add_match( $match2, $context_value );

		$expected = [
			'context_id' => 1,
			'context' => $context_value,
			'match_count' => 2,
			'matches' => [
				[
					'pos_id' => 0,
					'context_offset' => 0,
					'match' => 'one',
					'replacement' => 'two',
					'captures' => [],
				],
				[
					'pos_id' => 13,
					'context_offset' => 13,
					'match' => 'one',
					'replacement' => 'two',
					'captures' => [],
				],
			],
		];

		$this->assertEquals( $expected, $context->to_json() );

		// Check matches within context
		$this->assertTrue( $context->is_within_context( $match2 ) );
		$this->assertTrue( $context->is_within_context( $match3 ) );
		$this->assertFalse( $context->is_within_context( $match4 ) );

		// Check we get our matches back
		$this->assertEquals( $match1, $context->get_match_at_position( 0 ) );
		$this->assertEquals( $match2, $context->get_match_at_position( 13 ) );

		// No match
		$this->assertFalse( $context->get_match_at_position( 4 ) );
	}

	public function testContextLimit() {
		$context_value = 'one there is one match here and at the end is one';

		$context = new Match_Context( 1 );

		for ( $i = 0; $i < Match_Context::MATCH_LIMIT + 1; $i++ ) {
			$context->add_match( new Match( 'a', $i, 'b' ), $context_value );
		}

		$json = $context->to_json();
		$this->assertEquals( Match_Context::MATCH_LIMIT + 1, $json['match_count'] );
		$this->assertEquals( Match_Context::MATCH_LIMIT, count( $json['matches'] ) );
	}
}
