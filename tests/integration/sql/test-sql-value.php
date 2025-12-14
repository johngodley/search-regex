<?php

use SearchRegex\Sql;

class Value_Test extends SearchRegex_Api_Test {
	public function testRaw() {
		$value = Sql\Value::safe_raw( 'this.(*' );

		$this->assertEquals( 'this.(*', $value->get_value() );
	}

	public function testColumn() {
		$value = Sql\Value::column( "this-_column.nam e\r" );

		$this->assertEquals( 'this-_column.nam e', $value->get_value() );
	}

	public function testTable() {
		$value = Sql\Value::table( "this-_table.nam e\r" );

		$this->assertEquals( 'this-_tablename', $value->get_value() );
	}
}
