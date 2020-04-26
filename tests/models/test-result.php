<?php

use SearchRegex\Result;
use SearchRegex\Source_Post;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Flags;
use SearchRegex\Source_Manager;

class ResultTest extends WP_UnitTestCase {
	public function testResult() {
		$sources = Source_Manager::get_all_sources();
		$source = new Source_Post( $sources[0], new Search_Flags(), new Source_Flags() );
		$raw = [
			'post_title' => 'title',
			'post_type' => 'post',
		];
		$result = new Result( 1, $source, [], $raw );

		$json = $result->to_json();

		$this->assertEquals( $raw, $result->get_raw() );
		$this->assertEquals( 1, $result->get_row_id() );
		$this->assertEquals( [
			'row_id' => 1,
			'source_type' => 'post',
			'source_name' => 'Posts',
			'columns' => [],
			'actions' => [],
			'title' => 'title',
		], $json );
	}
}
