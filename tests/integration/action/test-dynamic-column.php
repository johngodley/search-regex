<?php

use SearchRegex\Action;
use SearchRegex\Schema;

class Dynamic_Column_Test extends SearchRegex_Api_Test {
	/**
	 * @var \WP_Hook|null
	 */
	private $original_text_filter = null;

	private bool $had_original_text_filter = false;

	/**
	 * @var array<string, callable>
	 */
	private array $original_shortcodes = [];

	protected function setUp(): void {
		parent::setUp();

		global $shortcode_tags, $wp_filter;

		$this->original_shortcodes = $shortcode_tags;
		$this->had_original_text_filter = isset( $wp_filter['searchregex_text'] );
		if ( $this->had_original_text_filter ) {
			$this->original_text_filter = clone $wp_filter['searchregex_text'];
		}
	}

	protected function tearDown(): void {
		global $shortcode_tags, $wp_filter;

		if ( $this->had_original_text_filter ) {
			$wp_filter['searchregex_text'] = $this->original_text_filter;
		} else {
			unset( $wp_filter['searchregex_text'] );
		}

		$shortcode_tags = $this->original_shortcodes; // phpcs:ignore

		parent::tearDown();
	}

	private function get_dynamic_column() {
		return new Action\Dynamic_Column();
	}

	private function get_schema() {
		return new Schema\Source( [ 'type' => 'posts' ] );
	}

	public function testConstructorDoesNotChangeShortcodes() {
		global $shortcode_tags;

		add_shortcode( 'sentinel', fn() => 'sentinel' );
		$expected = $shortcode_tags;

		$this->get_dynamic_column();

		$this->assertSame( $expected, $shortcode_tags );
	}

	public function testReplacementScopesAndRestoresShortcodes() {
		global $shortcode_tags;

		add_shortcode( 'sentinel', fn() => 'sentinel' );
		$expected = $shortcode_tags;
		$dynamic_column = $this->get_dynamic_column();

		$result = $dynamic_column->replace_text( '[upper]hello[/upper][sentinel]', 1, '', [], $this->get_schema() );

		$this->assertSame( 'HELLO[sentinel]', $result );
		$this->assertSame( $expected, $shortcode_tags );
		$this->assertSame( 'sentinel', do_shortcode( '[sentinel]' ) );
	}

	public function testReplacementRestoresShortcodesAfterException() {
		global $shortcode_tags;

		add_shortcode( 'sentinel', fn() => 'sentinel' );
		$expected = $shortcode_tags;
		$add_shortcode = function ( $shortcodes ) {
			$shortcodes[] = 'explode';

			return $shortcodes;
		};
		$throw = function () {
			throw new RuntimeException( 'Shortcode failure' );
		};

		add_filter( 'searchregex_shortcodes', $add_shortcode );
		add_filter( 'searchregex_do_shortcode', $throw );
		$dynamic_column = $this->get_dynamic_column();

		try {
			$dynamic_column->replace_text( '[explode]', 1, '', [], $this->get_schema() );
			$this->fail( 'Expected shortcode failure' );
		} catch ( RuntimeException $error ) {
			$this->assertSame( 'Shortcode failure', $error->getMessage() );
		} finally {
			remove_filter( 'searchregex_shortcodes', $add_shortcode );
			remove_filter( 'searchregex_do_shortcode', $throw );
		}

		$this->assertSame( $expected, $shortcode_tags );
	}

	public function testRegistrationRestoresShortcodesAfterException() {
		global $shortcode_tags;

		add_shortcode( 'sentinel', fn() => 'sentinel' );
		$expected = $shortcode_tags;
		$add_shortcode = function ( $shortcodes ) {
			$shortcodes[] = 'bad code';

			return $shortcodes;
		};
		$throw = function ( $function_name ) {
			if ( $function_name === 'add_shortcode' ) {
				throw new RuntimeException( 'Registration failure' );
			}
		};

		add_filter( 'searchregex_shortcodes', $add_shortcode );
		add_action( 'doing_it_wrong_run', $throw );
		$this->setExpectedIncorrectUsage( 'add_shortcode' );
		$dynamic_column = $this->get_dynamic_column();

		try {
			$dynamic_column->replace_text( '[upper]hello[/upper]', 1, '', [], $this->get_schema() );
			$this->fail( 'Expected registration failure' );
		} catch ( RuntimeException $error ) {
			$this->assertSame( 'Registration failure', $error->getMessage() );
		} finally {
			remove_filter( 'searchregex_shortcodes', $add_shortcode );
			remove_action( 'doing_it_wrong_run', $throw );
		}

		$this->assertSame( $expected, $shortcode_tags );
	}

	public function testModifyDiscoversColumnsWithoutLeakingShortcodes() {
		global $shortcode_tags;

		add_shortcode( 'sentinel', fn() => 'sentinel' );
		$expected = $shortcode_tags;
		$schema = new Schema\Schema(
			[
				[
					'type' => 'posts',
					'table' => 'wp_posts',
					'columns' => [
						[ 'column' => 'title', 'type' => 'string' ],
						[ 'column' => 'slug', 'type' => 'string' ],
					],
				],
			]
		);
		$modify = new Action\Type\Modify(
			[
				[
					'source' => 'posts',
					'column' => 'title',
					'operation' => 'set',
					'replaceValue' => '[column name="slug"]',
				],
			],
			$schema
		);

		$this->assertSame( [ 'posts__slug', 'posts__title' ], $modify->get_view_columns() );
		$this->assertSame( $expected, $shortcode_tags );
	}

	public function testClassHasNoDestructorGadget() {
		$this->assertFalse( method_exists( Action\Dynamic_Column::class, '__destruct' ) );
	}
}
