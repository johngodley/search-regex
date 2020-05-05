<?php

use SearchRegex\Match;
use SearchRegex\Match_Column;
use SearchRegex\Match_Context;

class ColumnTest extends WP_UnitTestCase {
	private function get_context() {
		$context_value = 'one there is one match here and at the end is one';
		$match1 = new Match( 'one', 0, 'two' );
		$match2 = new Match( 'one', 13, 'two' );

		$context = new Match_Context( 1 );
		$context->add_match( $match1, $context_value );
		$context->add_match( $match2, $context_value );

		return $context;
	}

	public function testEmptyColumn() {
		$column = new Match_Column( 'column', 'Label', 'replacement', [] );
		$expected = [
			'column_id' => 'column',
			'column_label' => 'Label',
			'contexts' => [],
			'context_count' => 0,
			'match_count' => 0,
			'replacement' => 'replacement',
		];

		$this->assertEquals( $expected, $column->to_json() );
		$this->assertEquals( 'column', $column->get_column_id() );
		$this->assertEquals( 0, $column->get_match_count() );
		$this->assertEquals( 'replacement', $column->get_replacement( null, '' ) );
		$this->assertFalse( $column->get_replacement( 343, 'something' ) );
	}

	public function testColumnWithMatches() {
		$column = new Match_Column( 'column', 'Label', 'replacement', [ $this->get_context() ] );
		$expected = [
			'column_id' => 'column',
			'column_label' => 'Label',
			'contexts' => [
				[
					'context_id' => 1,
					'context' => 'one there is one match here and at the end is one',
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
				],
			],
			'context_count' => 1,
			'match_count' => 2,
			'replacement' => 'replacement',
		];

		$this->assertEquals( $expected, $column->to_json() );
		$this->assertEquals( 'column', $column->get_column_id() );
		$this->assertEquals( 2, $column->get_match_count() );

		$this->assertFalse( $column->get_replacement( 13, 'one there is one match here and at the end is one' ) );
	}

	public function testContextLimit() {
		$contexts = [];
		for ( $i = 0; $i < Match_Column::CONTEXT_LIMIT + 1; $i++ ) {
			$contexts[] = $this->get_context();
		}

		$column = new Match_Column( 'column', 'Label', 'replacement', $contexts );
		$json = $column->to_json();

		$this->assertEquals( Match_Column::CONTEXT_LIMIT + 1, $json['context_count'] );
		$this->assertEquals( Match_Column::CONTEXT_LIMIT, count( $json['contexts'] ) );
	}
}
