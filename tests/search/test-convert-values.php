<?php

use SearchRegex\Source;
use SearchRegex\Schema;

class ConvertValues_Test extends SearchRegex_Api_Test {
	public function testDate() {
		$convert = new Source\Convert_Values();
		$column = new Schema\Column( [], new Schema\Source( [] ) );

		$this->assertEquals( 'February 23, 2012 6:12 am', $convert->get_date( $column, '2012-02-23 06:12:45' ) );
	}
}
