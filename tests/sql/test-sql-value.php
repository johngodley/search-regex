<?php

use SearchRegex\Sql\Sql_Value;

class Sql_Value_Test extends SearchRegex_Api_Test {
	public function testRaw() {
		$value = Sql_Value::safe_raw( 'this.(*' );

		$this->assertEquals( 'this.(*', $value->get_value() );
	}

	public function testColumn() {
		$value = Sql_Value::column( "this-_column.nam e\r" );

		$this->assertEquals( 'this-_column.nam e', $value->get_value() );
	}

	public function testTable() {
		$value = Sql_Value::table( "this-_table.nam e\r" );

		$this->assertEquals( 'this-_tablename', $value->get_value() );
	}
}
