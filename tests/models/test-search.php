<?php

use SearchRegex\Search;
use SearchRegex\Action;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Manager;
use SearchRegex\Schema;
use SearchRegex\Global_Search_Filter;
use SearchRegex\Search_Filter;

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
		$flags = $regex ? [ 'regex', 'case', ] : [ 'case' ];
		$schema = new Schema( Source_Manager::get_schema( $sources ) );

		$filters = [ new Global_Search_Filter( $search_phrase, $flags ) ];
		$sources = Source_Manager::get( $sources, $filters );

		$search = new Search( $sources );
		$action = Action::create( 'replace', [ 'search' => $search_phrase, 'replacement' => $replace_phrase, 'flags' => $flags ], $schema );

		return [ $search, $action ];
	}

	private function paginate_results( $search, $action, $expected_progress, $expected_totals ) {
		// Page through results
		$next = 0;
		foreach ( $expected_progress as $progress ) {
			$results = $search->get_search_results( $action, $next, 25 );

			$this->assertEquals( $expected_totals, $results['totals'] );
			$this->assertEquals( $progress, $results['progress'] );

			$next = $progress['next'];
		}
	}

	public function testNoMatchedText() {
		list( $search, $action ) = $this->get_search_replace( [ 'posts' ], 'zxyw', 'cat', false );

		$results = $search->get_search_results( $action, 0, 25 );
		$expected_totals = [
			'rows' => 199,
			'matched_rows' => 0,
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
		list( $search, $action ) = $this->get_search_replace( [ 'posts' ], 'the', 'cat', false );

		$expected_totals = [
			'rows' => 199,
			'matched_rows' => 33,
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
				'rows' => 8,
				'previous' => 0,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $action, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSource() {
		list( $search, $action ) = $this->get_search_replace( [ 'posts', 'comment' ], 'the', 'cat', false );

		$expected_totals = [
			'rows' => 300,
			'matched_rows' => 40,
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
				'rows' => 15,
				'previous' => 0,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $action, $expected_progress, $expected_totals );
	}

	public function testSearchSingleSourceRegex() {
		list( $search, $action ) = $this->get_search_replace( [ 'posts' ], 'the\w', 'cat', true );

		$expected_totals = [
			'rows' => 199,
			'matched_rows' => 0,  // regex = no matches
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
				'rows' => 4,
				'previous' => 0,
				'next' => 50,
			],
			[
				'current' => 50,
				'rows' => 2,
				'previous' => 25,
				'next' => 75,
			],
			[
				'current' => 75,
				'rows' => 4,
				'previous' => 50,
				'next' => 100,
			],
			[
				'current' => 100,
				'rows' => 1,
				'previous' => 75,
				'next' => 125,
			],
			[
				'current' => 125,
				'rows' => 5,
				'previous' => 100,
				'next' => 150,
			],
			[
				'current' => 150,
				'rows' => 5,
				'previous' => 125,
				'next' => 175,
			],
			[
				'current' => 175,
				'rows' => 1,
				'previous' => 150,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $action, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSourceRegex() {
		list( $search, $action ) = $this->get_search_replace( [ 'posts', 'comment' ], 'the\w', 'cat', true );

		$expected_totals = [
			'rows' => 300,
			'matched_rows' => 0,
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
				'rows' => 4,
				'previous' => 0,
				'next' => 50,
			],
			[
				'current' => 50,
				'rows' => 2,
				'previous' => 25,
				'next' => 75,
			],
			[
				'current' => 75,
				'rows' => 4,
				'previous' => 50,
				'next' => 100,
			],
			[
				'current' => 100,
				'rows' => 1,
				'previous' => 75,
				'next' => 125,
			],
			[
				'current' => 125,
				'rows' => 5,
				'previous' => 100,
				'next' => 150,
			],
			[
				'current' => 150,
				'rows' => 5,
				'previous' => 125,
				'next' => 175,
			],
			[
				'current' => 175,
				'rows' => 1,
				'previous' => 150,
				'next' => 200,
			],
			[
				'current' => 200,
				'rows' => 4,
				'previous' => 175,
				'next' => 225,
			],
			[
				'current' => 225,
				'rows' => 0,
				'previous' => 200,
				'next' => 250,
			],
			[
				'current' => 250,
				'rows' => 0,
				'previous' => 225,
				'next' => 275,
			],
			[
				'current' => 275,
				'rows' => 0,
				'previous' => 250,
				'next' => false,
			],
		];

		$this->paginate_results( $search, $action, $expected_progress, $expected_totals );
	}

	public function testSearchMultipleSourceRegexLimit() {
		list( $search, $action ) = $this->get_search_replace( [ 'posts', 'comment' ], 'the\w', 'cat', true );

		$expected = [
			'current' => 25,
			'rows' => 2,
			'previous' => 0,
			'next' => 27,
		];
		$results = $search->get_search_results( $action, 25, 25, 2 );
		$this->assertEquals( $expected, $results['progress'] );
	}
}
