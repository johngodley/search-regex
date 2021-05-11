<?php

use SearchRegex\Convert_Values;
use SearchRegex\Schema_Column;
use SearchRegex\Schema_Source;

class ConvertValues_Test extends SearchRegex_Api_Test {
	public function testDate() {
		$convert = new Convert_Values();
		$column = new Schema_Column( [], new Schema_Source( [] ) );

		$this->assertEquals( 'February 23, 2012 6:12 am', $convert->get_date( $column, '2012-02-23 06:12:45' ) );
	}
}
