<?php

use SearchRegex\Sql;

class Sql_Select_Test extends SearchRegex_Api_Test {
	public function testSelect() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ) );

		$this->assertEquals( 'column', $select->get_as_sql() );
	}

	public function testSelectPrefix() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ) );
		$select->set_prefix_required();

		$this->assertEquals( 'posts.column', $select->get_as_sql() );
	}

	public function testSelectAlias() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ), Sql\Value::column( 'cats' ) );
		$select->set_prefix_required();

		$this->assertEquals( 'posts.column AS cats', $select->get_as_sql() );
	}
}
