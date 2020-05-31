<?php

use SearchRegex\Totals;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Flags;
use SearchRegex\Source_Manager;

class TotalsTest extends Redirection_Api_Test {
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
			'matched_phrases' => 0,
		];

		$this->assertEquals( $expected_grand, $totals->to_json() );
		$this->assertEquals( 0, $totals->get_total_for_source( 'source', false ) );
	}

	public function testSingleSource() {
		$expected = [
			'rows' => 59,
			'matched_rows' => 15,
			'matched_phrases' => 38,
		];

		$sources = Source_Manager::get( [ 'post' ], new Search_Flags( [] ), new Source_Flags( [] ) );

		$totals = new Totals();
		$this->assertTrue( $totals->get_totals( $sources, 'the' ) );

		$this->assertEquals( 59, $totals->get_total_for_source( 'post', true ) );
		$this->assertEquals( 15, $totals->get_total_for_source( 'post', false ) );
		$this->assertEquals( $expected, $totals->to_json() );
	}

	public function testMultipleSource() {
		$expected = [
			'rows' => 159,
			'matched_rows' => 22,
			'matched_phrases' => 48,
		];

		$sources = Source_Manager::get( [ 'post', 'comment' ], new Search_Flags( [] ), new Source_Flags( [] ) );

		$totals = new Totals();
		$this->assertTrue( $totals->get_totals( $sources, 'the' ) );

		$this->assertEquals( 59, $totals->get_total_for_source( 'post', true ) );
		$this->assertEquals( 15, $totals->get_total_for_source( 'post', false ) );
		$this->assertEquals( 100, $totals->get_total_for_source( 'comment', true ) );
		$this->assertEquals( 7, $totals->get_total_for_source( 'comment', false ) );

		$this->assertEquals( $expected, $totals->to_json() );
	}
}
