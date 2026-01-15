<?php
/**
 * Unit tests for the Context\Value_Type class.
 *
 * @package Search_Regex
 */

use SearchRegex\Context;
use Brain\Monkey\Functions;

class ValueTypeTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/context/class-value-type.php';
	}

	protected function setUp(): void {
		parent::setUp();

		// Mock is_serialized to behave like WordPress
		Functions\when( 'is_serialized' )->alias( function( $data ) {
			// Simplified check for serialized data (matches WordPress behavior)
			if ( ! is_string( $data ) ) {
				return false;
			}
			$data = trim( $data );
			if ( 'N;' === $data ) {
				return true;
			}
			if ( preg_match( '/^([adObis]):/', $data, $matches ) ) {
				switch ( $matches[1] ) {
					case 'a':
					case 'O':
					case 's':
						if ( preg_match( "/^{$matches[1]}:[0-9]+:.*[;}]\$/s", $data ) ) {
							return true;
						}
						break;
					case 'b':
					case 'i':
					case 'd':
						if ( preg_match( "/^{$matches[1]}:[0-9.E+-]+;\$/", $data ) ) {
							return true;
						}
						break;
				}
			}
			return false;
		} );
	}

	public function testDetectsPlainText() {
		$this->assertEquals( Context\Value_Type::VALUE_TEXT, Context\Value_Type::get( 'text' ) );
	}

	public function testDetectsJson() {
		$this->assertEquals( Context\Value_Type::VALUE_JSON, Context\Value_Type::get( json_encode( [ 'this' => 'that' ] ) ) );
	}

	public function testDetectsJsonArray() {
		$this->assertEquals( Context\Value_Type::VALUE_JSON, Context\Value_Type::get( '[1, 2, 3]' ) );
	}

	public function testDetectsHtml() {
		$this->assertEquals( Context\Value_Type::VALUE_HTML, Context\Value_Type::get( '<strong>test</strong>' ) );
	}

	public function testDetectsHtmlWithAttributes() {
		$this->assertEquals( Context\Value_Type::VALUE_HTML, Context\Value_Type::get( '<div class="test">content</div>' ) );
	}

	public function testDetectsWordPressBlocks() {
		$this->assertEquals( Context\Value_Type::VALUE_BLOCKS, Context\Value_Type::get( '<!-- wp:paragrap -->' ) );
	}

	public function testDetectsWordPressBlocksWithContent() {
		$this->assertEquals( Context\Value_Type::VALUE_BLOCKS, Context\Value_Type::get( '<!-- wp:paragraph -->Hello<!-- /wp:paragraph -->' ) );
	}

	public function testDetectsPhpSerialized() {
		$this->assertEquals( Context\Value_Type::VALUE_PHP, Context\Value_Type::get( serialize( [ 'this' => 'that' ] ) ) );
	}

	public function testDetectsPhpSerializedString() {
		$this->assertEquals( Context\Value_Type::VALUE_PHP, Context\Value_Type::get( 's:5:"hello";' ) );
	}

	public function testDetectsPhpSerializedInteger() {
		$this->assertEquals( Context\Value_Type::VALUE_PHP, Context\Value_Type::get( 'i:42;' ) );
	}

	public function testPlainTextDoesNotMatchPartialHtml() {
		// Opening tag without closing tag should be text
		$this->assertEquals( Context\Value_Type::VALUE_TEXT, Context\Value_Type::get( '<strong>test' ) );
	}
}
