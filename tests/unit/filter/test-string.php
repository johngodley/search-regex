<?php
/**
 * Unit tests for the Filter\Type\Filter_String class.
 *
 * @package Search_Regex
 */

use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Sql;
use Brain\Monkey\Functions;

class FilterStringTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Use the plugin's autoloader
		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();

		// Mock $wpdb global
		global $wpdb;
		$wpdb = new class {
			public function prepare( $format, ...$args ) {
				$value = $args[0] ?? '';
				if ( $format === '%d' ) {
					return (string) intval( $value );
				}
				if ( $format === '%s' ) {
					return "'" . addslashes( $value ) . "'";
				}
				return "'" . addslashes( $value ) . "'";
			}

			public function esc_like( $text ) {
				return addcslashes( $text, '_%\\' );
			}
		};
	}

	protected function tearDown(): void {
		global $wpdb;
		$wpdb = null;
		parent::tearDown();
	}

	private function unescapeLike( string $sql ): string {
		return preg_replace( '/\{.*?\}/', '%', $sql );
	}

	private function getFilter( array $options ): Filter\Type\Filter_String {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'title' ], $source );
		return new Filter\Type\Filter_String( $options, $column );
	}

	private function getQueryForFilter( Filter\Type\Filter_String $filter ): Sql\Query {
		$query = $filter->get_query();
		$query->add_from( new Sql\From( Sql\Value::table( 'posts' ) ) );
		return $query;
	}

	public function testDefault() {
		$filter = $this->getFilter( [] );
		$expected = [
			'column' => 'title',
			'logic' => 'equals',
			'value' => '',
			'flags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'value' => [], 'logic' => 'monkey' ];
		$filter = $this->getFilter( $options );
		$expected = [
			'column' => 'title',
			'logic' => 'equals',
			'value' => '',
			'flags' => [ 'case' ],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testEquals() {
		$options = [ 'value' => "cat's", 'logic' => 'equals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE 'cat\'s'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testNotEquals() {
		$options = [ 'value' => "cat's", 'logic' => 'notequals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title NOT LIKE 'cat\'s'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testContains() {
		$options = [ 'value' => "c%at's", 'logic' => 'contains' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE '%c\\\\%at\'s%'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testNotContains() {
		$options = [ 'value' => "cat's", 'logic' => 'notcontains' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title NOT LIKE '%cat\'s%'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testBegins() {
		$options = [ 'value' => "cat's", 'logic' => 'begins' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE 'cat\'s%'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testEnds() {
		$options = [ 'value' => "cat's", 'logic' => 'ends' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT title FROM posts WHERE title LIKE '%cat\'s'", $this->unescapeLike( $query->get_as_sql() ) );
	}

	public function testIsValid() {
		$filter = $this->getFilter( [ 'value' => 'test' ] );
		$this->assertTrue( $filter->is_valid() );
	}

	public function testIsValidWithEmptyValue() {
		// Empty string is considered "having a value" in the implementation
		$filter = $this->getFilter( [ 'value' => '' ] );
		$this->assertTrue( $filter->is_valid() );
	}

	public function testIsNotValidWithNoValue() {
		// No value key at all means not valid
		$filter = $this->getFilter( [] );
		$this->assertFalse( $filter->is_valid() );
	}
}
