<?php
/**
 * Unit tests for the Action\Type\Export class.
 *
 * @package Search_Regex
 */

use SearchRegex\Action\Type\Export;
use SearchRegex\Schema;

class ExportTest extends TestCase {
	private Export $export;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$schema = new Schema\Schema( [ [ 'type' => 'posts' ] ] );
		$this->export = new Export( [ 'format' => 'csv' ], $schema );
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function preventFormulaInjection( $value ) {
		$reflection = new ReflectionClass( $this->export );
		$method = $reflection->getMethod( 'prevent_formula_injection' );
		return $method->invoke( $this->export, $value );
	}

	public function testSafeValueIsUnchanged() {
		$this->assertEquals( 'Hello World', $this->preventFormulaInjection( 'Hello World' ) );
	}

	public function testEmptyStringIsUnchanged() {
		$this->assertEquals( '', $this->preventFormulaInjection( '' ) );
	}

	public function testWhitespaceOnlyStringIsUnchanged() {
		$this->assertEquals( "  \t", $this->preventFormulaInjection( "  \t" ) );
	}

	public function testNonStringValueIsUnchanged() {
		$this->assertSame( 42, $this->preventFormulaInjection( 42 ) );
	}

	public function testEqualsFormulaIsPrefixed() {
		$this->assertEquals( '[FORMULA] =cmd|\'/c calc\'!A1', $this->preventFormulaInjection( '=cmd|\'/c calc\'!A1' ) );
	}

	public function testPlusFormulaIsPrefixed() {
		$this->assertEquals( '[FORMULA] +1+1', $this->preventFormulaInjection( '+1+1' ) );
	}

	public function testMinusFormulaIsPrefixed() {
		$this->assertEquals( '[FORMULA] -1+1', $this->preventFormulaInjection( '-1+1' ) );
	}

	public function testAtFormulaIsPrefixed() {
		$this->assertEquals( '[FORMULA] @SUM(A1:A2)', $this->preventFormulaInjection( '@SUM(A1:A2)' ) );
	}

	public function testFormulaBehindLeadingSpacesIsPrefixed() {
		$this->assertEquals( '[FORMULA]   =cmd()', $this->preventFormulaInjection( '  =cmd()' ) );
	}

	public function testFormulaBehindLeadingTabIsPrefixed() {
		$this->assertEquals( "[FORMULA] \t=cmd()", $this->preventFormulaInjection( "\t=cmd()" ) );
	}

	public function testValueContainingButNotStartingWithFormulaCharIsUnchanged() {
		$this->assertEquals( 'total=5', $this->preventFormulaInjection( 'total=5' ) );
	}
}
