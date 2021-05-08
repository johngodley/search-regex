<?php

use SearchRegex\Match;
use SearchRegex\Match_Context_String;
use SearchRegex\Search_Flags;

class MatchTest extends WP_UnitTestCase {
	private function get_matches_as_json( $matches ) {
		$json = [];
		foreach ( $matches as $match ) {
			$json[] = $match->to_json();
		}

		return $json;
	}

	public function testMatched_Item() {
		$match = new Matched_Item( 'match', 20, 'replace' );
		$match->set_context( 30 );
		$match->add_capture( 'test' );

		$json = $match->to_json();

		$this->assertEquals( 20, $match->get_position() );
		$this->assertEquals( 'match', $match->get_matched_text() );
		$this->assertEquals( [
			'pos_id' => 20,
			'context_offset' => 30,
			'match' => 'match',
			'replacement' => 'replace',
			'captures' => [ 'test' ],
		], $json );
	}

	public function testPlainPattern() {
		$pattern = Matched_Item::get_pattern( 'hello', new Search_Flags() );
		$this->assertEquals( '@hello@u', $pattern );
	}

	public function testPlainPatternWithRegex() {
		$pattern = Matched_Item::get_pattern( '@hello[', new Search_Flags() );
		$this->assertEquals( '@\@hello\[@u', $pattern );
	}

	public function testRegexPattern() {
		$pattern = Matched_Item::get_pattern( 'regex', new Search_Flags( [ 'regex' ] ) );
		$this->assertEquals( '@regex@u', $pattern );
	}

	public function testRegexPatternWithRegex() {
		$pattern = Matched_Item::get_pattern( 'regex[]@', new Search_Flags( [ 'regex' ] ) );
		$this->assertEquals( '@regex[]\\@@u', $pattern );
	}

	public function testRegexPatternWithRegexCase() {
		$pattern = Matched_Item::get_pattern( 'regex[]@', new Search_Flags( [ 'regex', 'case' ] ) );
		$this->assertEquals( '@regex[]\\@@iu', $pattern );
	}

	public function testNoMatches() {
		$search = 'search';
		$column_value = 'there is no match here';
		$flags = new Search_Flags();
		$replacements = [];
		$expected = [];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );

		$this->assertEquals( $expected, $matches );
	}

	public function testPlainMatchesSameContext() {
		$search = 'one';
		$column_value = 'one there is one match here and at the end is one';
		$flags = new Search_Flags();
		$replacements = [];

		$expected = [
			[
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 3,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
					[
						'pos_id' => 46,
						'context_offset' => 46,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testPlainMatchesMultipleContexts() {
		$search = 'one';
		$column_value = 'one there is one match here                                                                          and at the end is one';
		$flags = new Search_Flags();
		$replacements = [];

		$expected = [
			[
				'context_id' => 0,
				'context' => 'one there is one match here                                                 ',
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
				],
			],
			[
				'context_id' => 1,
				'context' => '                                and at the end is one',
				'match_count' => 1,
				'matches' => [
					[
						'pos_id' => 119,
						'context_offset' => 50,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testPlainMatchesNoCase() {
		$search = 'one';
		$column_value = 'ONE there is ONE match here and at the end is one';
		$flags = new Search_Flags( [ 'case' ] );
		$replacements = [];

		$expected = [
			[
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 3,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'ONE',
						'replacement' => '',
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'ONE',
						'replacement' => '',
						'captures' => [],
					],
					[
						'pos_id' => 46,
						'context_offset' => 46,
						'match' => 'one',
						'replacement' => '',
						'captures' => [],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testRegexMatches() {
		$search = 'one(\w+)';
		$column_value = 'onething there is one match here and at the end is onemore';
		$flags = new Search_Flags( [ 'regex' ] );
		$replacements = [];

		$expected = [
			[
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'onething',
						'replacement' => '',
						'captures' => [ 'thing' ],
					],
					[
						'pos_id' => 51,
						'context_offset' => 51,
						'match' => 'onemore',
						'replacement' => '',
						'captures' => [ 'more' ],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testRegexMatchesNoCase() {
		$search = 'one(\w+)';
		$column_value = 'ONEthing there is one match here and at the end is onemore';
		$flags = new Search_Flags( [ 'regex', 'case' ] );
		$replacements = [];

		$expected = [
			[
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'ONEthing',
						'replacement' => '',
						'captures' => [ 'thing' ],
					],
					[
						'pos_id' => 51,
						'context_offset' => 51,
						'match' => 'onemore',
						'replacement' => '',
						'captures' => [ 'more' ],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testUtfMatched_Item() {
		$search = 'one(\w+)';
		$column_value = 'ONE中国 there is one match here and at the end is onemore';
		$flags = new Search_Flags( [ 'regex', 'case' ] );
		$replacements = [ 'cat', 'dog' ];

		$expected = [
			[
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'ONE中国',
						'replacement' => 'cat',
						'captures' => [ '中国' ],
					],
					[
						'pos_id' => 48,
						'context_offset' => 48,
						'match' => 'onemore',
						'replacement' => 'dog',
						'captures' => [ 'more' ],
					],
				],
			],
		];

		$matches = Matched_Item::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->get_matches_as_json( $matches );

		$this->assertEquals( $expected, $json );
	}
}
