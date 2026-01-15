<?php
/**
 * Unit tests for the Sql\Select\Select class.
 *
 * @package Search_Regex
 */

use SearchRegex\Sql;

class SqlSelectTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/sql/class-value.php';
		require_once PLUGIN_PATH . '/includes/sql/select/class-select.php';
	}

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

	public function testSelectSameAlias() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ), Sql\Value::column( 'column' ) );
		$select->set_prefix_required();

		$this->assertEquals( 'posts.column', $select->get_as_sql() );
	}

	public function testGetColumnOrAlias() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ) );

		$this->assertEquals( 'posts.column', $select->get_column_or_alias() );
	}

	public function testGetColumnOrAliasWithAlias() {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ), Sql\Value::column( 'alias' ) );

		$this->assertEquals( 'alias.column', $select->get_column_or_alias() );
	}
}
