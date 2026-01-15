<?php
/**
 * Unit tests for the Sql\Value class.
 *
 * @package Search_Regex
 */

use SearchRegex\Sql;

class SqlValueTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/sql/class-value.php';
	}

	public function testSafeRawPreservesValue() {
		$value = Sql\Value::safe_raw( 'this.(*' );

		$this->assertEquals( 'this.(*', $value->get_value() );
	}

	public function testColumnSanitizesSpecialChars() {
		$value = Sql\Value::column( "this-_column.nam e\r" );

		$this->assertEquals( 'this-_column.nam e', $value->get_value() );
	}

	public function testTableSanitizesSpecialChars() {
		$value = Sql\Value::table( "this-_table.nam e\r" );

		$this->assertEquals( 'this-_tablename', $value->get_value() );
	}

	public function testColumnAllowsAlphanumericUnderscoreDashDotSpace() {
		$value = Sql\Value::column( 'Valid_Column-Name.alias 123' );

		$this->assertEquals( 'Valid_Column-Name.alias 123', $value->get_value() );
	}

	public function testTableAllowsAlphanumericUnderscoreDash() {
		$value = Sql\Value::table( 'Valid_Table-Name123' );

		$this->assertEquals( 'Valid_Table-Name123', $value->get_value() );
	}

	public function testColumnRemovesSqlInjectionAttempts() {
		$value = Sql\Value::column( "column; DROP TABLE users;--" );

		$this->assertEquals( 'column DROP TABLE users--', $value->get_value() );
	}

	public function testTableRemovesSqlInjectionAttempts() {
		$value = Sql\Value::table( "table; DROP TABLE users;--" );

		$this->assertEquals( 'tableDROPTABLEusers--', $value->get_value() );
	}

	public function testSafeRawDoesNotSanitize() {
		// This is intentional - safe_raw trusts the caller
		$value = Sql\Value::safe_raw( "dangerous; DROP TABLE users;--" );

		$this->assertEquals( "dangerous; DROP TABLE users;--", $value->get_value() );
	}
}
