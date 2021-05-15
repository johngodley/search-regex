<?php

use SearchRegex\Action_Nothing;
use SearchRegex\Schema_Column;
use SearchRegex\Schema_Source;
use SearchRegex\Search_Source;
use SearchRegex\Search_Filter_Member;
use SearchRegex\Source_Post;
use SearchRegex\Schema;
use SearchRegex\Match_Context_Matched;
use SearchRegex\Match_Context_Value;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_From;

class Filter_Member_Test extends SearchRegex_Api_Test {
	private function get_filter( $options ) {
		$api_options = [ [ 'value' => 'page', 'label' => 'Page' ] ];
		$source = new Schema_Source( [ 'type' => 'posts' ] );
		$column = new Schema_Column( [ 'column' => 'term_id', 'options' => $api_options ], $source );
		return new Search_Filter_Member( $options, $column );
	}

	private function get_query_for_filter( $filter ) {
		$query = $filter->get_query();
		$query->add_from( new Sql_From( Sql_Value::table( 'posts' ) ) );

		return $query;
	}

	private function get_data_for_filter( $filter, $value ) {
		$schema = new Schema( [ [ 'type' => 'posts'] ] );
		return $filter->get_column_data( '', $value, new Source_Post( [], [ $filter ] ), new Action_Nothing( [], $schema ) );
	}

	public function testDefault() {
		$filter = $this->get_filter( [] );
		$expected = [
			'column' => 'term_id',
			'logic' => 'include',
			'values' => [],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'values' => 1, 'logic' => 'monkey' ];
		$filter = $this->get_filter( $options );
		$expected = [
			'column' => 'term_id',
			'logic' => 'include',
			'values' => [],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testIncludeQuery() {
		$options = [ 'values' => [ 1, 2 ], 'logic' => 'include' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( 'SELECT term_id FROM posts WHERE term_id IN (1, 2)', $query->get_as_sql() );
	}

	public function testExcludeQuery() {
		$options = [ 'values' => [ 'cat', 'dog\'s' ], 'logic' => 'exclude' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT term_id FROM posts WHERE term_id NOT IN ('cat', 'dog\'s')", $query->get_as_sql() );
	}

	public function testGetDataInvalid() {
		$options = [];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '1' );
		$this->assertInstanceOf( Match_Context_Value::class, $results[0] );
	}

	public function testGetDataIncludes() {
		$options = [ 'values' => [ 1, 2 ], 'logic' => 'include' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '1' );
		$this->assertInstanceOf( Match_Context_Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '3,5' );
		$this->assertInstanceOf( Match_Context_Value::class, $results[0] );
	}

	public function testGetDataExcludes() {
		$options = [ 'values' => [ 'cat', 'dog\'s' ], 'logic' => 'exclude' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'monkey' );
		$this->assertInstanceOf( Match_Context_Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'dog\'s' );
		$this->assertInstanceOf( Match_Context_Value::class, $results[0] );
	}

	public function testIncludeOptions() {
		$options = [ 'values' => [ 'page' ], 'logic' => 'include' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'page' );
		$this->assertInstanceOf( Match_Context_Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'post' );
		$this->assertInstanceOf( Match_Context_Value::class, $results[0] );
	}

	// TODO: join
}
