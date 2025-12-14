<?php

use SearchRegex\Cli\Search_Regex_CLI;
use SearchRegex\Source;
use SearchRegex\Search;
use SearchRegex\Filter;
use SearchRegex\Action;
use SearchRegex\Schema;

class SearchRegexCLITest extends WP_UnitTestCase {
	/**
	 * Test CLI instance
	 *
	 * @var Search_Regex_CLI
	 */
	private $cli;

	/**
	 * Set up test fixtures
	 */
	public function setUp() {
		parent::setUp();
		$this->cli = new Search_Regex_CLI();

		// Create some test posts
		$this->factory->post->create( [
			'post_title' => 'Test Post with Hello World',
			'post_content' => 'This is a test post content.',
			'post_status' => 'publish',
		] );

		$this->factory->post->create( [
			'post_title' => 'Another Post',
			'post_content' => 'Hello World appears in this content.',
			'post_status' => 'publish',
		] );

		$this->factory->post->create( [
			'post_title' => 'Third Post',
			'post_content' => 'No matching text here.',
			'post_status' => 'publish',
		] );
	}

	/**
	 * Test extract_matched_texts method with valid matches
	 */
	public function test_extract_matched_texts_with_matches() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => 'Hello' ],
								[ 'match' => 'World' ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 2, $matched_texts );
		$this->assertEquals( 'Hello', $matched_texts[0] );
		$this->assertEquals( 'World', $matched_texts[1] );
	}

	/**
	 * Test extract_matched_texts method truncates long text
	 */
	public function test_extract_matched_texts_truncates_long_text() {
		$long_text = str_repeat( 'a', 100 );
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => $long_text ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 1, $matched_texts );
		$this->assertEquals( 50, strlen( $matched_texts[0] ) );
		$this->assertStringEndsWith( '...', $matched_texts[0] );
	}

	/**
	 * Test extract_matched_texts with no matches
	 */
	public function test_extract_matched_texts_with_no_matches() {
		$result = [
			'columns' => [],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 0, $matched_texts );
	}

	/**
	 * Test extract_matched_texts with multiple columns
	 */
	public function test_extract_matched_texts_with_multiple_columns() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => 'First' ],
							],
						],
					],
				],
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => 'Second' ],
								[ 'match' => 'Third' ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 3, $matched_texts );
		$this->assertEquals( 'First', $matched_texts[0] );
		$this->assertEquals( 'Second', $matched_texts[1] );
		$this->assertEquals( 'Third', $matched_texts[2] );
	}

	/**
	 * Test extract_matched_texts with multiple contexts
	 */
	public function test_extract_matched_texts_with_multiple_contexts() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => 'Context1Match1' ],
							],
						],
						[
							'matches' => [
								[ 'match' => 'Context2Match1' ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 2, $matched_texts );
		$this->assertEquals( 'Context1Match1', $matched_texts[0] );
		$this->assertEquals( 'Context2Match1', $matched_texts[1] );
	}

	/**
	 * Test extract_matched_texts handles missing match key
	 */
	public function test_extract_matched_texts_handles_missing_match_key() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'other_key' => 'value' ],
								[ 'match' => 'ValidMatch' ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 1, $matched_texts );
		$this->assertEquals( 'ValidMatch', $matched_texts[0] );
	}

	/**
	 * Test extract_matched_texts with empty contexts
	 */
	public function test_extract_matched_texts_with_empty_contexts() {
		$result = [
			'columns' => [
				[
					'contexts' => [],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 0, $matched_texts );
	}

	/**
	 * Test extract_matched_texts with empty matches
	 */
	public function test_extract_matched_texts_with_empty_matches() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 0, $matched_texts );
	}

	/**
	 * Test that special characters in matches are preserved
	 */
	public function test_extract_matched_texts_preserves_special_characters() {
		$result = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => '<html>' ],
								[ 'match' => 'test@example.com' ],
								[ 'match' => 'hello & goodbye' ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts = $method->invoke( $this->cli, $result );

		$this->assertCount( 3, $matched_texts );
		$this->assertEquals( '<html>', $matched_texts[0] );
		$this->assertEquals( 'test@example.com', $matched_texts[1] );
		$this->assertEquals( 'hello & goodbye', $matched_texts[2] );
	}

	/**
	 * Test exact truncation length
	 */
	public function test_extract_matched_texts_exact_truncation_length() {
		// Text exactly 50 characters should not be truncated
		$exact_50 = str_repeat( 'a', 50 );
		$result_50 = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => $exact_50 ],
							],
						],
					],
				],
			],
		];

		// Text 51 characters should be truncated
		$text_51 = str_repeat( 'a', 51 );
		$result_51 = [
			'columns' => [
				[
					'contexts' => [
						[
							'matches' => [
								[ 'match' => $text_51 ],
							],
						],
					],
				],
			],
		];

		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		$method->setAccessible( true );

		$matched_texts_50 = $method->invoke( $this->cli, $result_50 );
		$matched_texts_51 = $method->invoke( $this->cli, $result_51 );

		// 50 chars should not be truncated
		$this->assertEquals( 50, strlen( $matched_texts_50[0] ) );
		$this->assertStringEndsNotWith( '...', $matched_texts_50[0] );

		// 51 chars should be truncated to 50 (47 + '...')
		$this->assertEquals( 50, strlen( $matched_texts_51[0] ) );
		$this->assertStringEndsWith( '...', $matched_texts_51[0] );
	}
}
