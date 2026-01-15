<?php
/**
 * Unit tests for the Filter\Type\Filter_Member class.
 *
 * @package Search_Regex
 */

use SearchRegex\Filter;
use SearchRegex\Schema;
use SearchRegex\Sql;

class FilterMemberTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$this->setUpWpdb();
	}

	private function getFilter( array $options ): Filter\Type\Filter_Member {
		$api_options = [ [ 'value' => 'page', 'label' => 'Page' ] ];
		$source = new Schema\Source( [ 'type' => 'posts' ] );
		$column = new Schema\Column( [ 'column' => 'term_id', 'options' => $api_options ], $source );
		return new Filter\Type\Filter_Member( $options, $column );
	}

	private function getQueryForFilter( Filter\Type\Filter_Member $filter ): Sql\Query {
		$query = $filter->get_query();
		$query->add_from( new Sql\From( Sql\Value::table( 'posts' ) ) );
		return $query;
	}

	public function testDefault() {
		$filter = $this->getFilter( [] );
		$expected = [
			'column' => 'term_id',
			'logic' => 'include',
			'values' => [],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testBadInput() {
		$options = [ 'values' => 1, 'logic' => 'monkey' ];
		$filter = $this->getFilter( $options );
		$expected = [
			'column' => 'term_id',
			'logic' => 'include',
			'values' => [],
		];

		$this->assertEquals( $expected, $filter->to_json() );
		$this->assertFalse( $filter->is_valid() );
	}

	public function testIncludeQuery() {
		$options = [ 'values' => [ 1, 2 ], 'logic' => 'include' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( 'SELECT term_id FROM posts WHERE term_id IN (1, 2)', $query->get_as_sql() );
	}

	public function testExcludeQuery() {
		$options = [ 'values' => [ 'cat', "dog's" ], 'logic' => 'exclude' ];
		$filter = $this->getFilter( $options );
		$query = $this->getQueryForFilter( $filter );

		$this->assertEquals( "SELECT term_id FROM posts WHERE (term_id NOT IN ('cat', 'dog\'s') OR term_id IS NULL)", $query->get_as_sql() );
	}

	public function testIsValidWithValues() {
		$options = [ 'values' => [ 1, 2 ], 'logic' => 'include' ];
		$filter = $this->getFilter( $options );
		$this->assertTrue( $filter->is_valid() );
	}

	public function testIsNotValidWithEmptyValues() {
		$options = [ 'values' => [], 'logic' => 'include' ];
		$filter = $this->getFilter( $options );
		$this->assertFalse( $filter->is_valid() );
	}
}
