<?php

use SearchRegex\Source;
use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Context;
use SearchRegex\Action;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_From;

class Filter_Date_Test extends SearchRegex_Api_Test {
	private function get_filter( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date', 'joined_by' => 'user' ], $source );
		return new Filter\Type\Filter_Date( $options, $column );
	}

	private function get_query_for_filter( $filter ) {
		$query = $filter->get_query();
		$query->add_from( new Sql_From( Sql_Value::table( 'posts' ) ) );

		return $query;
	}

	private function get_data_for_filter( $filter, $value ) {
		$schema = new Schema\Schema( [ [ 'type' => 'posts'] ] );
		return $filter->get_column_data( '', $value, new Source\Core\Post( [], [ $filter ] ), new Action\Type\Nothing( [], $schema ) );
	}

	public function testDefault() {
		$filter = $this->get_filter( [] );
		$expected = [
			'column' => 'date',
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
			'column' => 'date',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testRangeQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'endValue' => '2002-01-01 01:01:01', 'logic' => 'range' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE (date > '2001-01-01 01:01:01' AND date < '2002-01-01 01:01:01')", $query->get_as_sql() );
	}

	public function testOtherQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE date = '2001-01-01 01:01:01'", $query->get_as_sql() );
	}

	public function testGetDataInvalid() {
		$options = [];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataEquals() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:02' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataNotEquals() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'notequals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:02' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataGreater() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'greater' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:00' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:03' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataLess() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'less' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:00' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:02' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataRange() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'endValue' => '2001-01-01 01:01:15', 'logic' => 'range' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:00' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:01' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, '2001-01-01 01:01:02' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}
}
