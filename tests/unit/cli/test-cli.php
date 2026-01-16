<?php
/**
 * Unit tests for the Search_Regex_CLI class.
 *
 * @package Search_Regex
 */

use SearchRegex\Cli\Search_Regex_CLI;

class SearchRegexCLITest extends TestCase {
	private Search_Regex_CLI $cli;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$this->cli = new Search_Regex_CLI();
	}

	private function invokeExtractMatchedTexts( array $result ): array {
		$reflection = new ReflectionClass( $this->cli );
		$method = $reflection->getMethod( 'extract_matched_texts' );
		return $method->invoke( $this->cli, $result );
	}

	public function testExtractMatchedTextsWithMatches() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 2, $matched_texts );
		$this->assertEquals( 'Hello', $matched_texts[0] );
		$this->assertEquals( 'World', $matched_texts[1] );
	}

	public function testExtractMatchedTextsTruncatesLongText() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 1, $matched_texts );
		$this->assertEquals( 50, strlen( $matched_texts[0] ) );
		$this->assertStringEndsWith( '...', $matched_texts[0] );
	}

	public function testExtractMatchedTextsWithNoMatches() {
		$result = [
			'columns' => [],
		];

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 0, $matched_texts );
	}

	public function testExtractMatchedTextsWithMultipleColumns() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 3, $matched_texts );
		$this->assertEquals( 'First', $matched_texts[0] );
		$this->assertEquals( 'Second', $matched_texts[1] );
		$this->assertEquals( 'Third', $matched_texts[2] );
	}

	public function testExtractMatchedTextsWithMultipleContexts() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 2, $matched_texts );
		$this->assertEquals( 'Context1Match1', $matched_texts[0] );
		$this->assertEquals( 'Context2Match1', $matched_texts[1] );
	}

	public function testExtractMatchedTextsHandlesMissingMatchKey() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 1, $matched_texts );
		$this->assertEquals( 'ValidMatch', $matched_texts[0] );
	}

	public function testExtractMatchedTextsWithEmptyContexts() {
		$result = [
			'columns' => [
				[
					'contexts' => [],
				],
			],
		];

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 0, $matched_texts );
	}

	public function testExtractMatchedTextsWithEmptyMatches() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 0, $matched_texts );
	}

	public function testExtractMatchedTextsPreservesSpecialCharacters() {
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

		$matched_texts = $this->invokeExtractMatchedTexts( $result );

		$this->assertCount( 3, $matched_texts );
		$this->assertEquals( '<html>', $matched_texts[0] );
		$this->assertEquals( 'test@example.com', $matched_texts[1] );
		$this->assertEquals( 'hello & goodbye', $matched_texts[2] );
	}

	public function testExtractMatchedTextsExactTruncationLength() {
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

		$matched_texts_50 = $this->invokeExtractMatchedTexts( $result_50 );
		$matched_texts_51 = $this->invokeExtractMatchedTexts( $result_51 );

		// 50 chars should not be truncated
		$this->assertEquals( 50, strlen( $matched_texts_50[0] ) );
		$this->assertStringEndsNotWith( '...', $matched_texts_50[0] );

		// 51 chars should be truncated to 50 (47 + '...')
		$this->assertEquals( 50, strlen( $matched_texts_51[0] ) );
		$this->assertStringEndsWith( '...', $matched_texts_51[0] );
	}
}
