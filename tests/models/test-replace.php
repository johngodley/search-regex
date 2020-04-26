<?php

use SearchRegex\Replace;
use SearchRegex\Source_Post;
use SearchRegex\Search_Flags;
use SearchRegex\Source_Manager;
use SearchRegex\Source_Flags;

class ReplaceTest extends WP_UnitTestCase {
	public function testReplacePositionsRegex() {
		$sources = Source_Manager::get_all_sources();
		$flags = new Search_Flags( [ 'regex' ] );
		$source = new Source_Post( $sources[0], $flags, new Source_Flags() );
		$value = 'dog';

		$replace = new Replace( $value, $sources, $flags );
		$positions = $replace->get_replace_positions( 'one(\w+)', 'ONE中国 there is one match here and at the end is onemore' );

		$this->assertEquals( [ 'dog' ], $positions );
	}

	// TODO save_and_replace
}
