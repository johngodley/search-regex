<?php
/**
 * Unit tests for the Source\Convert_Values class.
 *
 * @package Search_Regex
 */

use SearchRegex\Source;
use SearchRegex\Schema;
use Brain\Monkey\Functions;

class ConvertValuesTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/schema/class-source.php';
		require_once PLUGIN_PATH . '/includes/schema/class-column.php';
		require_once PLUGIN_PATH . '/includes/source/class-convert-values.php';
	}

	public function testGetDateFormatsCorrectly() {
		Functions\when( 'mysql2date' )->alias( function( $format, $value ) {
			// Simulate WordPress mysql2date returning a Unix timestamp
			return strtotime( $value );
		} );

		Functions\when( 'get_option' )->alias( function( $option ) {
			if ( $option === 'date_format' ) {
				return 'F j, Y';
			}
			if ( $option === 'time_format' ) {
				return 'g:i a';
			}
			return '';
		} );

		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [], new Schema\Source( [] ) );

		$this->assertEquals( 'February 23, 2012 6:12 am', $convert->get_date( $column, '2012-02-23 06:12:45' ) );
	}

	public function testGetDateReturnsOriginalOnInvalidDate() {
		Functions\when( 'mysql2date' )->justReturn( false );

		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [], new Schema\Source( [] ) );

		$this->assertEquals( 'invalid-date', $convert->get_date( $column, 'invalid-date' ) );
	}

	public function testConvertReturnsValueWhenNoMethodExists() {
		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [ 'column' => 'unknown_column', 'type' => 'unknown_type' ], new Schema\Source( [] ) );

		$this->assertEquals( 'some value', $convert->convert( $column, 'some value' ) );
	}

	public function testConvertCallsMethodByColumnName() {
		Functions\when( 'mysql2date' )->alias( function( $format, $value ) {
			return strtotime( $value );
		} );

		Functions\when( 'get_option' )->alias( function( $option ) {
			if ( $option === 'date_format' ) {
				return 'Y-m-d';
			}
			if ( $option === 'time_format' ) {
				return 'H:i';
			}
			return '';
		} );

		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [ 'column' => 'date' ], new Schema\Source( [] ) );

		$this->assertEquals( '2023-05-15 10:30', $convert->convert( $column, '2023-05-15 10:30:00' ) );
	}

	public function testConvertCallsMethodByType() {
		Functions\when( 'mysql2date' )->alias( function( $format, $value ) {
			return strtotime( $value );
		} );

		Functions\when( 'get_option' )->alias( function( $option ) {
			if ( $option === 'date_format' ) {
				return 'Y-m-d';
			}
			if ( $option === 'time_format' ) {
				return 'H:i';
			}
			return '';
		} );

		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [ 'column' => 'some_column', 'type' => 'date' ], new Schema\Source( [] ) );

		$this->assertEquals( '2023-05-15 10:30', $convert->convert( $column, '2023-05-15 10:30:00' ) );
	}

	public function testConvertCastsIntegerToString() {
		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [ 'column' => 'unknown', 'type' => 'unknown' ], new Schema\Source( [] ) );

		$this->assertEquals( '12345', $convert->convert( $column, 12345 ) );
	}
}
