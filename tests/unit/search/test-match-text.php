<?php
/**
 * Unit tests for the Search\Text class.
 *
 * @package Search_Regex
 */

use SearchRegex\Search;
use SearchRegex\Context;

class MatchTextTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/search/class-flags.php';
		require_once PLUGIN_PATH . '/includes/search/class-text.php';
		require_once PLUGIN_PATH . '/includes/context/class-context.php';
		require_once PLUGIN_PATH . '/includes/context/class-value-type.php';
		require_once PLUGIN_PATH . '/includes/context/type/class-text.php';
	}

	private function getMatchesAsJson( array $matches ): array {
		$json = [];
		foreach ( $matches as $match ) {
			$json[] = $match->to_json();
		}
		return $json;
	}

	public function testMatchedText() {
		$match = new Search\Text( 'match', 20, 'replace' );
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
		$pattern = Search\Text::get_pattern( 'hello', new Search\Flags() );
		$this->assertEquals( '@hello@u', $pattern );
	}

	public function testPlainPatternWithRegex() {
		$pattern = Search\Text::get_pattern( '@hello[', new Search\Flags() );
		$this->assertEquals( '@\@hello\[@u', $pattern );
	}

	public function testRegexPattern() {
		$pattern = Search\Text::get_pattern( 'regex', new Search\Flags( [ 'regex' ] ) );
		$this->assertEquals( '@regex@u', $pattern );
	}

	public function testRegexPatternWithRegex() {
		$pattern = Search\Text::get_pattern( 'regex[]@', new Search\Flags( [ 'regex' ] ) );
		$this->assertEquals( '@regex[]\\@@u', $pattern );
	}

	public function testRegexPatternWithRegexCase() {
		$pattern = Search\Text::get_pattern( 'regex[]@', new Search\Flags( [ 'regex', 'case' ] ) );
		$this->assertEquals( '@regex[]\\@@iu', $pattern );
	}

	public function testNoMatches() {
		$search = 'search';
		$column_value = 'there is no match here';
		$flags = new Search\Flags();
		$replacements = [];
		$expected = [];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );

		$this->assertEquals( $expected, $matches );
	}

	public function testPlainMatchesSameContext() {
		$search = 'one';
		$column_value = 'one there is one match here and at the end is one';
		$flags = new Search\Flags();
		$replacements = [];

		$expected = [
			[
				'type' => 'string',
				'crop' => [],
				'search' => 'one',
				'flags' => [],
				'value_type' => 'text',
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 3,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
					[
						'pos_id' => 46,
						'context_offset' => 46,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
				],
			],
		];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testPlainMatchesMultipleContexts() {
		$search = 'one';
		$column_value = 'one there is one match here                                                                          and at the end is one';
		$flags = new Search\Flags();
		$replacements = [];

		$expected = [
			[
				'type' => 'string',
				'crop' => [
					'end' => 76,
				],
				'search' => 'one',
				'flags' => [],
				'value_type' => 'text',
				'context_id' => 0,
				'context' => 'one there is one match here                                                 ',
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
				],
			],
			[
				'type' => 'string',
				'crop' => [
					'start' => 69,
				],
				'search' => 'one',
				'flags' => [],
				'value_type' => 'text',
				'context_id' => 1,
				'context' => '                                and at the end is one',
				'match_count' => 1,
				'matches' => [
					[
						'pos_id' => 119,
						'context_offset' => 50,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
				],
			],
		];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testPlainMatchesNoCase() {
		$search = 'one';
		$column_value = 'ONE there is ONE match here and at the end is one';
		$flags = new Search\Flags( [ 'case' ] );
		$replacements = [];

		$expected = [
			[
				'type' => 'string',
				'crop' => [],
				'search' => 'one',
				'flags' => [ 'case' ],
				'value_type' => 'text',
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 3,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'ONE',
						'replacement' => null,
						'captures' => [],
					],
					[
						'pos_id' => 13,
						'context_offset' => 13,
						'match' => 'ONE',
						'replacement' => null,
						'captures' => [],
					],
					[
						'pos_id' => 46,
						'context_offset' => 46,
						'match' => 'one',
						'replacement' => null,
						'captures' => [],
					],
				],
			],
		];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testRegexMatches() {
		$search = 'one(\w+)';
		$column_value = 'onething there is one match here and at the end is onemore';
		$flags = new Search\Flags( [ 'regex' ] );
		$replacements = [];

		$expected = [
			[
				'type' => 'string',
				'crop' => [],
				'search' => 'one(\w+)',
				'flags' => [ 'regex' ],
				'value_type' => 'text',
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'onething',
						'replacement' => null,
						'captures' => [ 'thing' ],
					],
					[
						'pos_id' => 51,
						'context_offset' => 51,
						'match' => 'onemore',
						'replacement' => null,
						'captures' => [ 'more' ],
					],
				],
			],
		];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testRegexMatchesNoCase() {
		$search = 'one(\w+)';
		$column_value = 'ONEthing there is one match here and at the end is onemore';
		$flags = new Search\Flags( [ 'regex', 'case' ] );
		$replacements = [];

		$expected = [
			[
				'type' => 'string',
				'crop' => [],
				'search' => 'one(\w+)',
				'flags' => [ 'regex', 'case' ],
				'value_type' => 'text',
				'context_id' => 0,
				'context' => $column_value,
				'match_count' => 2,
				'matches' => [
					[
						'pos_id' => 0,
						'context_offset' => 0,
						'match' => 'ONEthing',
						'replacement' => null,
						'captures' => [ 'thing' ],
					],
					[
						'pos_id' => 51,
						'context_offset' => 51,
						'match' => 'onemore',
						'replacement' => null,
						'captures' => [ 'more' ],
					],
				],
			],
		];

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testUtfMatchedText() {
		$search = 'one(\w+)';
		$column_value = 'ONE中国 there is one match here and at the end is onemore';
		$flags = new Search\Flags( [ 'regex', 'case' ] );
		$replacements = [ 'cat', 'dog' ];

		$expected = [
			[
				'type' => 'string',
				'crop' => [],
				'search' => 'one(\w+)',
				'flags' => [ 'regex', 'case' ],
				'value_type' => 'text',
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

		$matches = Search\Text::get_all( $search, $flags, $replacements, $column_value );
		$json = $this->getMatchesAsJson( $matches );

		$this->assertEquals( $expected, $json );
	}

	public function testReplaceAtPosition() {
		$match = new Search\Text( 'world', 6, 'universe' );
		$text = 'Hello world!';

		$result = $match->replace_at_position( $text );

		$this->assertEquals( 'Hello universe!', $result );
	}

	public function testReplaceAtPositionWithUtf8() {
		$match = new Search\Text( '世界', 2, '宇宙' );
		$text = 'こん世界';

		$result = $match->replace_at_position( $text );

		$this->assertEquals( 'こん宇宙', $result );
	}

	public function testReplaceAtPositionWithNoReplacement() {
		$match = new Search\Text( 'world', 6 );
		$text = 'Hello world!';

		$result = $match->replace_at_position( $text );

		$this->assertEquals( 'Hello world!', $result );
	}

	public function testSetReplacement() {
		$match = new Search\Text( 'test', 0 );
		$match->set_replacement( 'replaced' );

		$json = $match->to_json();

		$this->assertEquals( 'replaced', $json['replacement'] );
	}
}
