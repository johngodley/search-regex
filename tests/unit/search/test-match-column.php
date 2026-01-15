<?php
/**
 * Unit tests for the Search\Column class.
 *
 * @package Search_Regex
 */

use SearchRegex\Context;
use SearchRegex\Search;

class MatchColumnTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	public function testEmptyColumn() {
		$column = new Search\Column( 'column', 'Label', [], [] );
		$expected = [
			'column_id' => 'column',
			'column_label' => 'Label',
			'contexts' => [],
			'context_count' => 0,
			'match_count' => 0,
		];

		$this->assertEquals( $expected, $column->to_json() );
		$this->assertEquals( 'column', $column->get_column_id() );
		$this->assertEquals( 0, $column->get_match_count() );
	}

	public function testColumnWithMatches() {
		$column = new Search\Column( 'column', 'Label', [ new Context\Type\Value( 'thing' ) ], [] );
		$expected = [
			'column_id' => 'column',
			'column_label' => 'Label',
			'contexts' => [
				[
					'context_id' => 0,
					'type' => 'value',
					'value' => 'thing',
					'value_type' => 'text',
					'value_label' => 'thing',
					'value_length' => 5,
				],
			],
			'context_count' => 1,
			'match_count' => 1,
		];

		$this->assertEquals( $expected, $column->to_json() );
		$this->assertEquals( 'column', $column->get_column_id() );
		$this->assertEquals( 1, $column->get_match_count() );
	}

	public function testContextLimit() {
		$contexts = [];
		for ( $i = 0; $i < Search\Column::CONTEXT_LIMIT + 1; $i++ ) {
			$contexts[] = new Context\Type\Value( 'thing ' . $i );
		}

		$column = new Search\Column( 'column', 'Label', $contexts, [] );
		$json = $column->to_json();

		$this->assertEquals( Search\Column::CONTEXT_LIMIT + 1, $json['context_count'] );
		$this->assertEquals( Search\Column::CONTEXT_LIMIT, count( $json['contexts'] ) );
	}

	public function testAddUnmatchedContexts() {
		$contexts = [ new Context\Type\Value( 'thing1' ), new Context\Type\Value( 'thing2' ) ];

		$column = new Search\Column( 'column', 'Label', [], [] );
		$column->add_contexts_if_matching( $contexts );

		$this->assertEquals( $contexts, $column->get_contexts() );
	}

	public function testAddMatchedContexts() {
		$contexts = [ new Context\Type\Matched( 'thing1' ), new Context\Type\Matched( 'thing2' ) ];

		$column = new Search\Column( 'column', 'Label', [], [] );
		$column->add_contexts_if_matching( $contexts );

		$this->assertEquals( $contexts, $column->get_contexts() );
	}

	public function testAddBothContexts() {
		$contexts = [ new Context\Type\Value( 'thing1' ), new Context\Type\Matched( 'thing2' ) ];

		$column = new Search\Column( 'column', 'Label', [], [] );
		$column->add_contexts_if_matching( $contexts );

		$this->assertEquals( [ $contexts[1] ], $column->get_contexts() );
	}

	public function testRemoveDuplicateContext() {
		$contexts = [ new Context\Type\Value( 'thing1' ), new Context\Type\Value( 'thing1' ) ];
		$column = new Search\Column( 'column', 'Label', $contexts, [] );

		$this->assertEquals( [ $contexts[0] ], $column->get_contexts() );
	}

	public function testSetContexts() {
		$column = new Search\Column( 'column', 'Label', [], [] );
		$contexts = [ new Context\Type\Value( 'test' ) ];
		$column->set_contexts( $contexts );

		$this->assertEquals( $contexts, $column->get_contexts() );
	}

	public function testMultipleMatchedContexts() {
		$contexts = [
			new Context\Type\Matched( 'match1' ),
			new Context\Type\Matched( 'match2' ),
			new Context\Type\Matched( 'match3' ),
		];

		$column = new Search\Column( 'column', 'Label', $contexts, [] );

		$this->assertEquals( 3, $column->get_match_count() );
	}

	public function testReplaceContext() {
		$context = new Context\Type\Replace( 'original' );
		$context->set_replacement( 'replaced' );

		$column = new Search\Column( 'column', 'Label', [ $context ], [] );

		$this->assertEquals( 1, $column->get_match_count() );
	}
}
