<?php
/**
 * Unit tests for the Filter\Type\Filter_Integer class.
 *
 * @package Search_Regex
 */

use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Sql;

class FilterIntegerTest extends TestCase {
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

	private function getFilter( array $options ): Filter\Type\Filter_Integer {
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'author', 'joined_by' => 'user' ], $source );
		return new Filter\Type\Filter_Integer( $options, $column );
	}

	private function getQueryForFilter( Filter\Type\Filter_Integer $filter ): Sql\Query {
		$query = $filter->get_query();
		$query->add_from( new Sql\From( Sql\Value::table( 'posts' ) ) );
		return $query;
	}

	public function testDefault() {
		$filter = $this->getFilter( [] );
		$expected = [
			'column' => 'author',
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
			'column' => 'author',
			'startValue' => 0,
			'endValue' => 0,
			'logic' => 'equals',
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertTrue( $filter->is_valid() );
	}

	public function testRangeQuery() {
		$options = [ 'startValue' => '1', 'endValue' => '3', 'logic' => 'range' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE (author >= 1 AND author <= 3)', $query->get_as_sql() );
	}

	public function testNotRangeQuery() {
		$options = [ 'startValue' => '1', 'endValue' => '3', 'logic' => 'notrange' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE (author <= 1 OR author >= 3)', $query->get_as_sql() );
	}

	public function testOtherQuery() {
		$options = [ 'startValue' => '1', 'logic' => 'equals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE author = 1', $query->get_as_sql() );
	}

	public function testGreaterQuery() {
		$options = [ 'startValue' => '5', 'logic' => 'greater' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE author > 5', $query->get_as_sql() );
	}

	public function testLessQuery() {
		$options = [ 'startValue' => '5', 'logic' => 'less' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE author < 5', $query->get_as_sql() );
	}

	public function testNotEqualsQuery() {
		$options = [ 'startValue' => '5', 'logic' => 'notequals' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT author FROM posts WHERE author != 5', $query->get_as_sql() );
	}
}
