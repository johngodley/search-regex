<?php

use SearchRegex\Sql\Sql_Value;
use SearchRegex\Sql\Sql_Select;

class Sql_Select_Test extends SearchRegex_Api_Test {
	public function testSelect() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );

		$this->assertEquals( 'column', $select->get_as_sql() );
	}

	public function testSelectPrefix() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ) );
		$select->set_prefix_required();

		$this->assertEquals( 'posts.column', $select->get_as_sql() );
	}

	public function testSelectAlias() {
		$select = new Sql_Select( Sql_Value::table( 'posts' ), Sql_Value::column( 'column' ), Sql_Value::column( 'cats' ) );
		$select->set_prefix_required();

		$this->assertEquals( 'posts.column AS cats', $select->get_as_sql() );
	}
}
