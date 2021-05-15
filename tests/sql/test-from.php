<?php

use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_From;

class Sql_From_Test extends SearchRegex_Api_Test {
	public function testFrom() {
		$from = new Sql_From( Sql_Value::table( 'table' ) );

		$this->assertEquals( 'table', $from->get_as_sql() );
	}

	public function testFromAlias() {
		$from = new Sql_From( Sql_Value::table( 'table' ), Sql_Value::table( 'other' ) );

		$this->assertEquals( 'table AS other', $from->get_as_sql() );
	}
}
