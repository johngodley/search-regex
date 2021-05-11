<?php

use SearchRegex\Value_Type;

class Value_TypeTest extends SearchRegex_Api_Test {
	public function testText() {
		$this->assertEquals( Value_Type::VALUE_TEXT, Value_Type::get( 'text' ) );
	}

	public function testJson() {
		$this->assertEquals( Value_Type::VALUE_JSON, Value_Type::get( json_encode( [ 'this' => 'that' ] ) ) );
	}

	public function testHtml() {
		$this->assertEquals( Value_Type::VALUE_HTML, Value_Type::get( '<strong>test</strong>' ) );
	}

	public function testBlocks() {
		$this->assertEquals( Value_Type::VALUE_BLOCKS, Value_Type::get( '<!-- wp:paragrap -->' ) );
	}

	public function testPhp() {
		$this->assertEquals( Value_Type::VALUE_PHP, Value_Type::get( serialize( [ 'this' => 'that' ] ) ) );
	}
}
