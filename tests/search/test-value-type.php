<?php

use SearchRegex\Context;

class Value_TypeTest extends SearchRegex_Api_Test {
	public function testText() {
		$this->assertEquals( Context\Value_Type::VALUE_TEXT, Context\Value_Type::get( 'text' ) );
	}

	public function testJson() {
		$this->assertEquals( Context\Value_Type::VALUE_JSON, Context\Value_Type::get( json_encode( [ 'this' => 'that' ] ) ) );
	}

	public function testHtml() {
		$this->assertEquals( Context\Value_Type::VALUE_HTML, Context\Value_Type::get( '<strong>test</strong>' ) );
	}

	public function testBlocks() {
		$this->assertEquals( Context\Value_Type::VALUE_BLOCKS, Context\Value_Type::get( '<!-- wp:paragrap -->' ) );
	}

	public function testPhp() {
		$this->assertEquals( Context\Value_Type::VALUE_PHP, Context\Value_Type::get( serialize( [ 'this' => 'that' ] ) ) );
	}
}
