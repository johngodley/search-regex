<?php

use SearchRegex\Search;
use SearchRegex\Replace;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Flags;
use SearchRegex\Source_Manager;

class ReplaceAllTest extends SearchRegex_Api_Test {
	public function setUp() {
		global $wpdb;

		_delete_all_posts();

		$wpdb->query( "DELETE FROM {$wpdb->comments}" );
		$wpdb->query( "ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1" );
		$wpdb->query( "ALTER TABLE {$wpdb->comments} AUTO_INCREMENT = 1" );

		$posts = self::load_fixture( 'posts.csv', 200 );
		$comments = self::load_fixture( 'comments.csv', 200 );

		self::create_posts_from_csv( $posts );
		self::create_comments_from_csv( $comments );

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_name LIKE 'post-title-%'" );
	}

	private function get_search_replace( $sources, $search_phrase, $replace_phrase, $regex ) {
		$flags = new Search_Flags( $regex ? [ 'regex', 'case' ] : [ 'case' ] );
		$sources = Source_Manager::get( $sources, $flags, new Source_Flags( [] ) );

		// Create a search and replacer
		$search = new Search( $search_phrase, $sources, $flags );
		$replacer = new Replace( $replace_phrase, $sources, $flags );

		return [ $search, $replacer ];
	}

	private function paginate_replace( $search, $replacer, $expected_row, $pages ) {
		$next = 0;
		$total = 0;

		foreach ( $pages as $pos => $page ) {
			$results = $search->get_replace_results( $replacer, $next, 25 );
			$replaced = $replacer->save_and_replace( $results['results'] );

			$this->assertEquals( $page['next'], $results['next'], 'Next is different: ' . $pos );
			$this->assertEquals( $page['rows'], $results['rows'], 'Rows is different: ' . $pos );
			$this->assertEquals( $page['totals'], $results['totals'], 'Totals is different: ' . $pos );
			$this->assertEquals( $page['rows_replaced'], $replaced['rows'], 'Rows replaced is different: ' . $pos );
			$this->assertEquals( $page['phrases'], $replaced['phrases'], 'Phrases is different: ' . $pos );

			$total += $page['rows'];

			$next = $results['next'];
		}

		$this->assertEquals( $expected_row, $total );
	}

	public function testReplaceSingle() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post' ], 'the', 'cat', false );

		$expected_row = 27;
		$pages = [
			[
				'next' => 'post-156',
				'rows' => 25,
				'rows_replaced' => 25,
				'phrases' => 70,
				'totals' => [
					'rows' => 113,
					'matched_rows' => 27,
					'matched_phrases' => 72,
				],
			],
			[
				'next' => false,
				'rows' => 2,
				'rows_replaced' => 2,
				'phrases' => 2,
				'totals' => [
					'rows' => 113,
					'matched_rows' => 2,
					'matched_phrases' => 2,
				],
			],
		];

		$this->paginate_replace( $search, $replacer, $expected_row, $pages );
	}

	public function testReplaceMultiple() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post', 'comment' ], 'the', 'cat', false );

		$expected_row = 34;
		$pages = [
			[
				'next' => 'post-156',
				'rows' => 25,
				'rows_replaced' => 25,
				'phrases' => 70,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 34,
					'matched_phrases' => 82,
				],
			],
			[
				'next' => false,
				'rows' => 9,
				'rows_replaced' => 9,
				'phrases' => 12,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 9,
					'matched_phrases' => 12,
				],
			],
		];

		$this->paginate_replace( $search, $replacer, $expected_row, $pages );
	}

	public function testReplaceMultipleRegex() {
		list( $search, $replacer ) = $this->get_search_replace( [ 'post', 'comment' ], 'the\w', 'cat', true );

		$expected_row = 214;
		$pages = [
			[
				'next' => 'post-55',
				'rows' => 25,
				'rows_replaced' => 7,
				'phrases' => 18,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'post-95',
				'rows' => 25,
				'rows_replaced' => 5,
				'phrases' => 11,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'post-136',
				'rows' => 25,
				'rows_replaced' => 6,
				'phrases' => 20,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'post-177',
				'rows' => 25,
				'rows_replaced' => 6,
				'phrases' => 15,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'comment-12',
				'rows' => 25,
				'rows_replaced' => 3,
				'phrases' => 3,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'comment-37',
				'rows' => 25,
				'rows_replaced' => 1,
				'phrases' => 1,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'comment-62',
				'rows' => 25,
				'rows_replaced' => 0,
				'phrases' => 0,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => 'comment-87',
				'rows' => 25,
				'rows_replaced' => 0,
				'phrases' => 0,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
			[
				'next' => false,
				'rows' => 14,
				'rows_replaced' => 0,
				'phrases' => 0,
				'totals' => [
					'rows' => 214,
					'matched_rows' => 0,
					'matched_phrases' => 0,
				],
			],
		];

		$this->paginate_replace( $search, $replacer, $expected_row, $pages );
	}
}
