<?php

use SearchRegex\Totals;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Manager;
use SearchRegex\Global_Search_Filter;

class TotalsTest extends SearchRegex_Api_Test {
	public static function setUpBeforeClass() {
		$posts = self::load_fixture( 'posts.csv', 100 );
		$comments = self::load_fixture( 'comments.csv', 100 );

		self::create_posts_from_csv( $posts );
		self::create_comments_from_csv( $comments );
	}

	public function testEmpty() {
		$totals = new Totals();
		$expected_grand = [
			'rows' => 0,
			'matched_rows' => 0,
		];

		$this->assertEquals( $expected_grand, $totals->to_json() );
		$this->assertEquals( 0, $totals->get_matched_rows_for_source( 'source' ) );
	}

	public function testSingleSource() {
		$expected = [
			'rows' => 100,
			'matched_rows' => 18,
		];

		$sources = Source_Manager::get( [ 'posts' ], [ new Global_Search_Filter( 'the', [] ) ] );

		$totals = new Totals();
		$this->assertTrue( $totals->get_totals( $sources ) );
		$this->assertEquals( $expected['rows'], $totals->get_total_rows_for_source( 'posts' ) );
		$this->assertEquals( $expected['matched_rows'], $totals->get_matched_rows_for_source( 'posts' ) );
		$this->assertEquals( $expected, $totals->to_json() );
	}

	public function testMultipleSource() {
		$expected = [
			'rows' => 200,
			'matched_rows' => 25,
		];

		$sources = Source_Manager::get( [ 'posts', 'comment' ], [ new Global_Search_Filter( 'the', [] ) ] );

		$totals = new Totals();
		$this->assertTrue( $totals->get_totals( $sources ) );

		$this->assertEquals( 100, $totals->get_total_rows_for_source( 'posts' ) );
		$this->assertEquals( 18, $totals->get_matched_rows_for_source( 'posts' ) );
		$this->assertEquals( 100, $totals->get_total_rows_for_source( 'comment' ) );
		$this->assertEquals( 7, $totals->get_matched_rows_for_source( 'comment' ) );

		$this->assertEquals( $expected, $totals->to_json() );
	}
}
