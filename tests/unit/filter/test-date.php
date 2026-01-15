<?php
/**
 * Unit tests for the Filter\Type\Filter_Date class.
 *
 * @package Search_Regex
 */

use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Sql;

class FilterDateTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$wpdb = new class {
			public $prefix = 'wp_';

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

	private function getFilter( array $options ): Filter\Type\Filter_Date {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'date', 'joined_by' => 'user' ], $source );
		return new Filter\Type\Filter_Date( $options, $column );
	}

	private function getQueryForFilter( Filter\Type\Filter_Date $filter ): Sql\Query {
		$query = $filter->get_query();
		$query->add_from( new Sql\From( Sql\Value::table( 'posts' ) ) );
		return $query;
	}

	public function testDefault() {
		$filter = $this->getFilter( [] );
		$expected = [
			'column' => 'date',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'startValue' => 'cats', 'endValue' => 'dogs', 'logic' => 'monkey' ];
		$filter = $this->getFilter( $options );
		$expected = [
			'column' => 'date',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testRangeQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'endValue' => '2002-01-01 01:01:01', 'logic' => 'range' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE (date > '2001-01-01 01:01:01' AND date < '2002-01-01 01:01:01')", $query->get_as_sql() );
	}

	public function testEqualsQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'equals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE date = '2001-01-01 01:01:01'", $query->get_as_sql() );
	}

	public function testNotEqualsQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'notequals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE date != '2001-01-01 01:01:01'", $query->get_as_sql() );
	}

	public function testGreaterQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'greater' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE date > '2001-01-01 01:01:01'", $query->get_as_sql() );
	}

	public function testLessQuery() {
		$options = [ 'startValue' => '2001-01-01 01:01:01', 'logic' => 'less' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT date FROM posts WHERE date < '2001-01-01 01:01:01'", $query->get_as_sql() );
	}
}
