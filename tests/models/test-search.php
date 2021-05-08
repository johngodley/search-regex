<?php

use SearchRegex\Search;
use SearchRegex\Replace;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Flags;
use SearchRegex\Source_Manager;

class SearchTest extends SearchRegex_Api_Test {
	public static function setUpBeforeClass() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts}" );
		$wpdb->query( "DELETE FROM {$wpdb->comments}" );

		$posts = self::load_fixture( 'posts.csv', 200 );
		$comments = self::load_fixture( 'comments.csv', 200 );

		self::create_posts_from_csv( $posts );
		self::create_comments_from_csv( $comments );
	}

	public function setUp() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_name LIKE 'post-title-%'" );
		$wpdb->query( "ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1" );
		$wpdb->query( "ALTER TABLE {$wpdb->comments} AUTO_INCREMENT = 1" );
	}

	private function get_search_replace( $sources, $search_phrase, $replace_phrase, $regex ) {
		$flags = new Search_Flags( $regex ? [ 'regex', 'case' ] : [ 'case' ] );
		$sources = Source_Manager::get( $sources, $flags, new Source_Flags( [] ) );

		// Create a search and replacer
		$search = new Search( $search_phrase, $sources, $flags );
		$replacer = new Replace( $replace_phrase, $sources, $flags );

		return [ $search, $replacer ];
	}

	private function paginate_results( $search, $replacer, $expected_progress, $expected_totals ) {
		// Page through results
		$next = 0;
		foreach ( $expected_progress as $progress ) {
			$results = $search->get_search_results( $replacer, $next, 25 );

			$this->assertEquals( $expected_totals, $results['totals'] );
			$this->assertEquals( $progress, $results['progress'] );

			$next = $progress['next'];
		}
	}

	public function testNoMatched_Item() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post' ], 'zxyw', 'cat', false );

		$results = $search->get_search_results( $replacer, 0, 25 );
		$expected_totals = [
			'rows' => 113,
			'matched_rows' => 0,
			'matched_phrases' => 0,
		];
		$expected_progress = [
			'current' => 0,
			'rows' => 0,
			'previous' => false,
			'next' => false,
		];

		$this->assertEquals( $expected_totals, $results['totals'] );
		$this->assertEquals( $expected_progress, $results['progress'] );
		$this->assertEquals( [], $results['results'] );
	}

	public function testSearchSingleSource() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post' ], 'the', 'cat', false );

		$expected_totals = [
			'rows' => 113,
			'matched_rows' => 27,
			'matched_phrases' => 72,
		];
		$expected_progress = [
			[
				'current' => 0,
				'rows' => 25,
				'previous' => false,
				'next' => 25,
			],
			[
				'current' => 25,
				'rows' => 2,
				'previous' => 0,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $replacer, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSource() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post', 'comment' ], 'the', 'cat', false );

		$expected_totals = [
			'rows' => 214,
			'matched_rows' => 34,
			'matched_phrases' => 82,
		];
		$expected_progress = [
			[
				'current' => 0,
				'rows' => 25,
				'previous' => false,
				'next' => 25,
			],
			[
				'current' => 25,
				'rows' => 9,
				'previous' => 0,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $replacer, $expected_progress, $expected_totals );
	}

	public function testSearchSingleSourceRegex() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post' ], 'the\w', 'cat', true );

		$expected_totals = [
			'rows' => 113,
			'matched_rows' => 0,
			'matched_phrases' => 0,
		];
		$expected_progress = [
			[
				'current' => 0,
				'rows' => 7,
				'previous' => false,
				'next' => 25,
			],
			[
				'current' => 25,
				'rows' => 5,
				'previous' => 0,
				'next' => 50,
			],
			[
				'current' => 50,
				'rows' => 6,
				'previous' => 25,
				'next' => 75,
			],
			[
				'current' => 75,
				'rows' => 6,
				'previous' => 50,
				'next' => 100,
			],
			[
				'current' => 100,
				'rows' => 0,
				'previous' => 75,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $replacer, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSourceRegex() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post', 'comment' ], 'the\w', 'cat', true );

		$expected_totals = [
			'rows' => 214,
			'matched_rows' => 0,
			'matched_phrases' => 0,
		];
		$expected_progress = [
			[
				'current' => 0,
				'rows' => 7,
				'previous' => false,
				'next' => 25,
			],
			[
				'current' => 25,
				'rows' => 5,
				'previous' => 0,
				'next' => 50,
			],
			[
				'current' => 50,
				'rows' => 6,
				'previous' => 25,
				'next' => 75,
			],
			[
				'current' => 75,
				'rows' => 6,
				'previous' => 50,
				'next' => 100,
			],
			[
				'current' => 100,
				'rows' => 3,
				'previous' => 75,
				'next' => 125,
			],
			[
				'current' => 125,
				'rows' => 1,
				'previous' => 100,
				'next' => 150,
			],
			[
				'current' => 150,
				'rows' => 0,
				'previous' => 125,
				'next' => 175,
			],
			[
				'current' => 175,
				'rows' => 0,
				'previous' => 150,
				'next' => 200,
			],
			[
				'current' => 200,
				'rows' => 0,
				'previous' => 175,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $replacer, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSourceRegexLimit() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post', 'comment' ], 'the\w', 'cat', true );

		$expected = [
			'current' => 25,
			'rows' => 2,
			'previous' => 0,
			'next' => 27,
		];
		$results = $search->get_search_results( $replacer, 25, 25, 2 );
		$this->assertEquals( $expected, $results['progress'] );
	}
}
