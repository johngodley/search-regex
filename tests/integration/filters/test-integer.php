<?php

use SearchRegex\Source;
use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Context;
use SearchRegex\Action;
use SearchRegex\Sql;

class Filter_Integer_Test extends SearchRegex_Api_Test {
	private function get_filter( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'author', 'joined_by' => 'user' ], $source );
		return new Filter\Type\Filter_Integer( $options, $column );
	}

	private function get_query_for_filter( $filter ) {
		$query = $filter->get_query();
		$query->add_from( new Sql\From( Sql\Value::table( 'posts' ) ) );

		return $query;
	}

	private function get_data_for_filter( $filter, $value ) {
		$schema = new Schema\Schema( [ [ 'type' => 'posts'] ] );
		return $filter->get_column_data( '', $value, new Source\Core\Post( [], [ $filter ] ), new Action\Type\Nothing( [], $schema ) );
	}

	public function testDefault() {
		$filter = $this->get_filter( [] );
		$expected = [
			'column' => 'author',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'startValue' => 'cats', 'endValue' => 'dogs', 'logic' => 'monkey' ];
		$filter = $this->get_filter( $options );
		$expected = [
			'column' => 'author',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertTrue( $filter->is_valid() );
	}

	public function testRangeQuery() {
		$options = [ 'startValue' => '1', 'endValue' => '3', 'logic' => 'range' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE (author >= 1 AND author <= 3)', $query->get_as_sql() );
	}

	public function testNotRangeQuery() {
		$options = [ 'startValue' => '1', 'endValue' => '3', 'logic' => 'notrange' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE (author <= 1 OR author >= 3)', $query->get_as_sql() );
	}

	public function testOtherQuery() {
		$options = [ 'startValue' => '1', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE author = 1', $query->get_as_sql() );
	}

	public function testJoinQueryHas() {
		$options = [ 'startValue' => '1', 'logic' => 'has' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );
		$query = $filter->modify_query( $query );

		$this->assertEquals( 'SELECT author, wp_posts.post_author FROM posts LEFT JOIN wp_users ON wp_users.ID=wp_posts.post_author WHERE wp_users.ID IS NOT NULL', $query->get_as_sql() );
	}

	public function testJoinQueryHasNot() {
		$options = [ 'startValue' => '1', 'logic' => 'hasnot' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );
		$query = $filter->modify_query( $query );

		$this->assertEquals( 'SELECT author, wp_posts.post_author FROM posts LEFT JOIN wp_users ON wp_users.ID=wp_posts.post_author WHERE wp_users.ID IS NULL', $query->get_as_sql() );
	}

	public function testGetDataInvalid() {
		$options = [];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataEquals() {
		$options = [ 'startValue' => '1', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataNotEquals() {
		$options = [ 'startValue' => '1', 'logic' => 'notequals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataGreater() {
		$options = [ 'startValue' => '2', 'logic' => 'greater' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 3 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataLess() {
		$options = [ 'startValue' => '2', 'logic' => 'less' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 3 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataRange() {
		$options = [ 'startValue' => 2, 'endValue' => 4, 'logic' => 'range' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 3 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataNotRange() {
		$options = [ 'startValue' => 2, 'endValue' => 4, 'logic' => 'notrange' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 1 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 2 );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 3 );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}
}
