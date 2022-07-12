<?php

use SearchRegex\Source;
use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Context;
use SearchRegex\Action;
use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_From;

class Filter_String_Test extends SearchRegex_Api_Test {
	private function unescape_like( $sql ) {
		return preg_replace( '/\{.*?\}/', '%', $sql );
	}

	private function get_filter( $options ) {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'title' ], $source );
		return new Filter\Type\Filter_String( $options, $column );
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
			'column' => 'title',
			'logic' => 'equals',
			'value' => '',
			'flags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'value' => [], 'logic' => 'monkey' ];
		$filter = $this->get_filter( $options );
		$expected = [
			'column' => 'title',
			'logic' => 'equals',
			'value' => '',
			'flags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testEquals() {
		$options = [ 'value' => 'cat\'s', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE 'cat\'s'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testNotEquals() {
		$options = [ 'value' => 'cat\'s', 'logic' => 'notequals' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title NOT LIKE 'cat\'s'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testContains() {
		$options = [ 'value' => 'c%at\'s', 'logic' => 'contains' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE '%c\\\%at\'s%'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testNotContains() {
		$options = [ 'value' => 'cat\'s', 'logic' => 'notcontains' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title NOT LIKE '%cat\'s%'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testBegins() {
		$options = [ 'value' => 'cat\'s', 'logic' => 'begins' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE 'cat\'s%'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testEnds() {
		$options = [ 'value' => 'cat\'s', 'logic' => 'ends' ];
		$filter = $this->get_filter( $options );
		$query = $this->get_query_for_filter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE '%cat\'s'", $this->unescape_like( $query->get_as_sql() ) );
	}

	public function testGetDataInvalid() {
		$options = [];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'cats' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataEquals() {
		$options = [ 'value' => 'cat', 'logic' => 'equals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'cat' );
		$this->assertInstanceOf( Context\Type\Text::class, $results[0] );

		$json = $results[0]->to_json();
		$this->assertEquals( 'cat', $json['context'] );
		$this->assertEquals( 'cat', $json['matches'][0]['match'] );
		$this->assertEquals( 0, $json['matches'][0]['pos_id'] );

		$results = $this->get_data_for_filter( $filter, 'dog' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataNotEquals() {
		$options = [ 'value' => 'cat', 'logic' => 'notequals' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'cat' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'dog' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataContains() {
		$options = [ 'value' => 'cat', 'logic' => 'contains' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'this is a cat and monkey' );
		$this->assertInstanceOf( Context\Type\Text::class, $results[0] );

		$json = $results[0]->to_json();
		$this->assertEquals( 'this is a cat and monkey', $json['context'] );
		$this->assertEquals( 'cat', $json['matches'][0]['match'] );
		$this->assertEquals( 10, $json['matches'][0]['pos_id'] );

		$results = $this->get_data_for_filter( $filter, 'this is a dog and monkey' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataNotContains() {
		$options = [ 'value' => 'cat', 'logic' => 'notcontains' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'this is a cat and monkey' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'this is a dog and monkey' );
		$this->assertInstanceOf( Context\Type\Matched::class, $results[0] );
	}

	public function testGetDataBegins() {
		$options = [ 'value' => 'cat', 'logic' => 'begins' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'cat and monkey' );
		$this->assertInstanceOf( Context\Type\Text::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'dog and monkey' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );
	}

	public function testGetDataNotEnds() {
		$options = [ 'value' => 'cat', 'logic' => 'ends' ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'this is a dog' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'this is a cat' );
		$this->assertInstanceOf( Context\Type\Text::class, $results[0] );
	}

	public function testGetDataRegex() {
		$options = [ 'value' => 'ca(.*)', 'logic' => 'equals', 'flags' => [ 'regex' ] ];
		$filter = $this->get_filter( $options );

		$results = $this->get_data_for_filter( $filter, 'this is a dog' );
		$this->assertInstanceOf( Context\Type\Value::class, $results[0] );

		$results = $this->get_data_for_filter( $filter, 'this is a cat' );
		$this->assertInstanceOf( Context\Type\Text::class, $results[0] );

		$json = $results[0]->to_json();
		$this->assertEquals( 'this is a cat', $json['context'] );
		$this->assertEquals( 'cat', $json['matches'][0]['match'] );
		$this->assertEquals( 10, $json['matches'][0]['pos_id'] );
		$this->assertEquals( [ 't' ], $json['matches'][0]['captures'] );
	}
}
