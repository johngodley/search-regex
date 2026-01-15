<?php
/**
 * Unit tests for the Sql\Where classes.
 *
 * @package Search_Regex
 */

use SearchRegex\Sql;
use SearchRegex\Search;

class SqlWhereTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/sql/class-value.php';
		require_once PLUGIN_PATH . '/includes/sql/select/class-select.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-integer.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-null.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-in.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-date.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-string.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-or.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where-and.php';
		require_once PLUGIN_PATH . '/includes/search/class-flags.php';
	}

	protected function setUp(): void {
		parent::setUp();

		// Mock $wpdb global
		global $wpdb;
		$wpdb = new class {
			public function prepare( $format, ...$args ) {
				$value = $args[0] ?? '';
				// Handle different format specifiers like WordPress does
				if ( $format === '%d' ) {
					return (string) intval( $value );
				}
				if ( $format === '%s' ) {
					return "'" . addslashes( $value ) . "'";
				}
				return "'" . addslashes( $value ) . "'";
			}

			public function esc_like( $text ) {
				// Escape % and _ for LIKE queries
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

	private function getSelect(): Sql\Select\Select {
		$select = new Sql\Select\Select( Sql\Value::table( 'posts' ), Sql\Value::column( 'column' ) );
		$select->set_prefix_required();
		return $select;
	}

	public function testWhereIntegerEquals() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 5 );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );
	}

	public function testWhereIntegerNotEquals() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'notequals', 5 );
		$this->assertEquals( 'posts.column != 5', $where->get_as_sql() );
	}

	public function testWhereIntegerBadValue() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'notequals', "bad's" );
		$this->assertEquals( 'posts.column != 0', $where->get_as_sql() );
	}

	public function testWhereIntegerGreater() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'greater', 5 );
		$this->assertEquals( 'posts.column > 5', $where->get_as_sql() );
	}

	public function testWhereIntegerLess() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'less', 5 );
		$this->assertEquals( 'posts.column < 5', $where->get_as_sql() );
	}

	public function testWhereIntegerBadLogic() {
		$where = new Sql\Where\Where_Integer( $this->getSelect(), 'cats', 5 );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );
	}

	public function testWhereNullIsNull() {
		$where = new Sql\Where\Where_Null( $this->getSelect(), 'hasnot' );
		$this->assertEquals( 'posts.column IS NULL', $where->get_as_sql() );
	}

	public function testWhereNullIsNotNull() {
		$where = new Sql\Where\Where_Null( $this->getSelect(), 'has' );
		$this->assertEquals( 'posts.column IS NOT NULL', $where->get_as_sql() );
	}

	public function testWhereNullBadLogic() {
		$where = new Sql\Where\Where_Null( $this->getSelect(), 'something' );
		$this->assertEquals( 'posts.column IS NULL', $where->get_as_sql() );
	}

	public function testWhereInIntegers() {
		$where = new Sql\Where\Where_In( $this->getSelect(), 'IN', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column IN (1, 2, 3, 4)', $where->get_as_sql() );
	}

	public function testWhereInStrings() {
		$where = new Sql\Where\Where_In( $this->getSelect(), 'IN', [ "cat's", 'dog', 'thing', 'giraffe' ] );
		$this->assertEquals( "posts.column IN ('cat\'s', 'dog', 'thing', 'giraffe')", $where->get_as_sql() );
	}

	public function testWhereNotIn() {
		$where = new Sql\Where\Where_In( $this->getSelect(), 'NOT IN', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column NOT IN (1, 2, 3, 4)', $where->get_as_sql() );
	}

	public function testWhereInBadLogic() {
		$where = new Sql\Where\Where_In( $this->getSelect(), 'SOMETHING', [ 1, 2, 3, 4 ] );
		$this->assertEquals( 'posts.column IN (1, 2, 3, 4)', $where->get_as_sql() );
	}

	public function testWhereDateEquals() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'equals', strtotime( '2002-01-02 12:13:14' ) );
		$this->assertEquals( "posts.column = '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereDateNotEquals() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'notequals', strtotime( '2002-01-02 12:13:14' ) );
		$this->assertEquals( "posts.column != '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereDateGreater() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'greater', strtotime( '2002-01-02 12:13:14' ) );
		$this->assertEquals( "posts.column > '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereDateLess() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'less', strtotime( '2002-01-02 12:13:14' ) );
		$this->assertEquals( "posts.column < '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereDateBadValue() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'equals', 'cat' );
		$this->assertEquals( "posts.column = '1970-01-01 00:00:00'", $where->get_as_sql() );
	}

	public function testWhereDateBadLogic() {
		$where = new Sql\Where\Where_Date( $this->getSelect(), 'cat', strtotime( '2002-01-02 12:13:14' ) );
		$this->assertEquals( "posts.column = '2002-01-02 12:13:14'", $where->get_as_sql() );
	}

	public function testWhereStringEquals() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'equals', "cat's and dog's" );
		$this->assertEquals( "posts.column LIKE 'cat\'s and dog\'s'", $where->get_as_sql() );
	}

	public function testWhereStringNotEquals() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'notequals', "cat's and dog's" );
		$this->assertEquals( "posts.column NOT LIKE 'cat\'s and dog\'s'", $where->get_as_sql() );
	}

	public function testWhereStringContains() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'contains', "cat" );
		$this->assertStringContainsString( "posts.column LIKE '%cat%'", $this->unescapeLike( $where->get_as_sql() ) );
	}

	public function testWhereStringNotContains() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'notcontains', "cat" );
		$this->assertStringContainsString( "posts.column NOT LIKE '%cat%'", $this->unescapeLike( $where->get_as_sql() ) );
	}

	public function testWhereStringBegins() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'begins', "cat" );
		$this->assertStringContainsString( "posts.column LIKE 'cat%'", $this->unescapeLike( $where->get_as_sql() ) );
	}

	public function testWhereStringEnds() {
		$where = new Sql\Where\Where_String( $this->getSelect(), 'ends', "cat" );
		$this->assertStringContainsString( "posts.column LIKE '%cat'", $this->unescapeLike( $where->get_as_sql() ) );
	}

	public function testWhereStringCaseSensitive() {
		$flags = new Search\Flags( [] ); // No 'case' flag = case sensitive
		$where = new Sql\Where\Where_String( $this->getSelect(), 'equals', 'Test', $flags );
		$this->assertEquals( "posts.column COLLATE utf8mb4_bin LIKE 'Test'", $where->get_as_sql() );
	}

	public function testWhereStringCaseInsensitive() {
		$flags = new Search\Flags( [ 'case' ] ); // 'case' flag = case insensitive
		$where = new Sql\Where\Where_String( $this->getSelect(), 'equals', 'Test', $flags );
		$this->assertEquals( "posts.column LIKE 'Test'", $where->get_as_sql() );
	}

	public function testWhereStringWithEmoji() {
		$flags = new Search\Flags( [] ); // case sensitive
		$where = new Sql\Where\Where_String( $this->getSelect(), 'contains', '💊', $flags );
		$this->assertStringContainsString( 'COLLATE utf8mb4_bin', $where->get_as_sql() );
		$this->assertStringContainsString( '💊', $where->get_as_sql() );
	}

	public function testWhereOrSingle() {
		$where = new Sql\Where\Where_Or( [ new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 5 ) ] );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );
	}

	public function testWhereOrMultiple() {
		$where = new Sql\Where\Where_Or( [
			new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 5 ),
			new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 6 ),
		] );
		$this->assertEquals( '(posts.column = 5 OR posts.column = 6)', $where->get_as_sql() );
	}

	public function testWhereAndSingle() {
		$where = new Sql\Where\Where_And( [ new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 5 ) ] );
		$this->assertEquals( 'posts.column = 5', $where->get_as_sql() );
	}

	public function testWhereAndMultiple() {
		$where = new Sql\Where\Where_And( [
			new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 5 ),
			new Sql\Where\Where_Integer( $this->getSelect(), 'equals', 6 ),
		] );
		$this->assertEquals( '(posts.column = 5 AND posts.column = 6)', $where->get_as_sql() );
	}
}
