<?php
/**
 * Unit tests for the Sql\From class.
 *
 * @package Search_Regex
 */

use SearchRegex\Sql;

class SqlFromTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/sql/class-value.php';
		require_once PLUGIN_PATH . '/includes/sql/class-from.php';
	}

	public function testFrom() {
		$from = new Sql\From( Sql\Value::table( 'table' ) );

		$this->assertEquals( 'table', $from->get_as_sql() );
	}

	public function testFromAlias() {
		$from = new Sql\From( Sql\Value::table( 'table' ), Sql\Value::table( 'other' ) );

		$this->assertEquals( 'table AS other', $from->get_as_sql() );
	}

	public function testFromSameAlias() {
		$from = new Sql\From( Sql\Value::table( 'table' ), Sql\Value::table( 'table' ) );

		$this->assertEquals( 'table', $from->get_as_sql() );
	}
}
