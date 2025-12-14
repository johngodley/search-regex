<?php

use SearchRegex\Sql;

class Sql_From_Test extends SearchRegex_Api_Test {
	public function testFrom() {
		$from = new Sql\From( Sql\Value::table( 'table' ) );

		$this->assertEquals( 'table', $from->get_as_sql() );
	}

	public function testFromAlias() {
		$from = new Sql\From( Sql\Value::table( 'table' ), Sql\Value::table( 'other' ) );

		$this->assertEquals( 'table AS other', $from->get_as_sql() );
	}
}
