<?php

use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Select;
use SearchRegex\Sql\Sql_Where_Integer;
use SearchRegex\Sql\Sql_Where_Null;
use SearchRegex\Sql\Sql_Where_In;
use SearchRegex\Sql\Sql_Where_Date;
use SearchRegex\Sql\Sql_Where_String;
use SearchRegex\Sql\Sql_Where_And;
use SearchRegex\Sql\Sql_Where_Or;

class Sql_Where_Test extends SearchRegex_Api_Test {
	private function unescape_like( $sql ) {
		return preg_replace( '/\{.*?\}/', '%', $sql );
	}

	public function testWhereInteger() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		// equals
		$where = new Sql_Where_Integer( $select, 'equals', 5 );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );

		// not equals
		$where = new Sql_Where_Integer( $select, 'notequals', 5 );
		$this->assertEquals( 'posts.column != 5', $where->get_as_sql() );

		// bad value
		$where = new Sql_Where_Integer( $select, 'notequals', "bad's" );
		$this->assertEquals( 'posts.column != 0', $where->get_as_sql() );

		// greater
		$where = new Sql_Where_Integer( $select, 'greater', 5 );
		$this->assertEquals( 'posts.column > 5', $where->get_as_sql() );

		// less
		$where = new Sql_Where_Integer( $select, 'less', 5 );
		$this->assertEquals( 'posts.column < 5', $where->get_as_sql() );

		// bad logic
		$where = new Sql_Where_Integer( $select, 'cats', 5 );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );
	}

	public function testWhereNull() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		// is not null
		$where = new Sql_Where_Null( $select, 'hasnot' );
		$this->assertEquals( 'posts.column IS NULL', $where->get_as_sql() );

		// is null
		$where = new Sql_Where_Null( $select, 'has' );
		$this->assertEquals( 'posts.column IS NOT NULL', $where->get_as_sql() );

		// bad logic
		$where = new Sql_Where_Null( $select, 'something' );
		$this->assertEquals( 'posts.column IS NULL', $where->get_as_sql() );
	}

	public function testWhereIn() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		// in integer
		$where = new Sql_Where_In( $select, 'IN', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column IN (1, 2, 3, 4)', $where->get_as_sql() );

		// in strings
		$where = new Sql_Where_In( $select, 'IN', [ "cat's", 'dog', 'thing', 'giraffe' ] );
		$this->assertEquals( "posts.column IN ('cat\'s', 'dog', 'thing', 'giraffe')", $where->get_as_sql() );

		// not in integer
		$where = new Sql_Where_In( $select, 'NOT IN', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column NOT IN (1, 2, 3, 4)', $where->get_as_sql() );

		// bad logic
		$where = new Sql_Where_In( $select, 'SOMETHING', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column IN (1, 2, 3, 4)', $where->get_as_sql() );
	}

	public function testWhereDate() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		// equals date
		$where = new Sql_Where_Date( $select, 'equals', strtotime( '2002:01:02 12:13:14' ) );
		$this->assertEquals( "posts.column = '2002-01-02 12:13:14'", $where->get_as_sql() );

		// not equals date
		$where = new Sql_Where_Date( $select, 'notequals', strtotime( '2002:01:02 12:13:14' ) );
		$this->assertEquals( "posts.column != '2002-01-02 12:13:14'", $where->get_as_sql() );

		// greater date
		$where = new Sql_Where_Date( $select, 'greater', strtotime( '2002:01:02 12:13:14' ) );
		$this->assertEquals( "posts.column > '2002-01-02 12:13:14'", $where->get_as_sql() );

		// less date
		$where = new Sql_Where_Date( $select, 'less', strtotime( '2002:01:02 12:13:14' ) );
		$this->assertEquals( "posts.column < '2002-01-02 12:13:14'", $where->get_as_sql() );

		// bad value
		$where = new Sql_Where_Date( $select, 'equals', 'cat' );
		$this->assertEquals( "posts.column = '1970-01-01 00:00:00'", $where->get_as_sql() );

		// bad logic
		$where = new Sql_Where_Date( $select, 'cat', strtotime( '2002:01:02 12:13:14' ) );
		$this->assertEquals( "posts.column = '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereString() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		// equals
		$where = new Sql_Where_String( $select, 'equals', "cat's and dog's" );
		$this->assertEquals( "posts.column LIKE 'cat\'s and dog\'s'", $where->get_as_sql() );

		// equals
		$where = new Sql_Where_String( $select, 'notequals', "cat's and dog's" );
		$this->assertEquals( "posts.column NOT LIKE 'cat\'s and dog\'s'", $where->get_as_sql() );

		// contains
		$where = new Sql_Where_String( $select, 'contains', "%cat's and dog's" );
		$this->assertEquals( "posts.column LIKE '%\\\\%cat\'s and dog\'s%'", $this->unescape_like( $where->get_as_sql() ) );

		// not contains
		$where = new Sql_Where_String( $select, 'notcontains', "%cat's and dog's" );
		$this->assertEquals( "posts.column NOT LIKE '%\\\\%cat\'s and dog\'s%'", $this->unescape_like( $where->get_as_sql() ) );

		// begins
		$where = new Sql_Where_String( $select, 'begins', "%cat's and dog's" );
		$this->assertEquals( "posts.column LIKE '\\\\%cat\'s and dog\'s%'", $this->unescape_like( $where->get_as_sql() ) );

		// ends
		$where = new Sql_Where_String( $select, 'ends', "%cat's and dog's" );
		$this->assertEquals( "posts.column LIKE '%\\\\%cat\'s and dog\'s'", $this->unescape_like( $where->get_as_sql() ) );

		// bad logic
		$where = new Sql_Where_String( $select, 'cats', "%cat's and dog's" );
		$this->assertEquals( "posts.column LIKE '\\\\%cat\'s and dog\'s'", $this->unescape_like( $where->get_as_sql() ) );
	}

	public function testWhereOr() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		$where = new Sql_Where_Or( [ new Sql_Where_Integer( $select, 'equals', 5 ) ] );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );

		$where = new Sql_Where_Or( [ new Sql_Where_Integer( $select, 'equals', 5 ), new Sql_Where_Integer( $select, 'equals', 6 ) ] );
		$this->assertEquals( '(posts.column = 5 OR posts.column = 6)', $where->get_as_sql() );
	}

	public function testWhereAnd() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		$where = new Sql_Where_And( [ new Sql_Where_Integer( $select, 'equals', 5 ) ] );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );

		$where = new Sql_Where_And( [ new Sql_Where_Integer( $select, 'equals', 5 ), new Sql_Where_Integer( $select, 'equals', 6 ) ] );
		$this->assertEquals( '(posts.column = 5 AND posts.column = 6)', $where->get_as_sql() );
	}
}
