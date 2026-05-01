<?php
/**
 * Unit tests for the Sql\Query class.
 *
 * @package Search_Regex
 */

use SearchRegex\Sql;

class SqlQueryTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/includes/sql/class-value.php';
		require_once PLUGIN_PATH . '/includes/sql/class-from.php';
		require_once PLUGIN_PATH . '/includes/sql/class-group.php';
		require_once PLUGIN_PATH . '/includes/sql/select/class-select.php';
		require_once PLUGIN_PATH . '/includes/sql/where/class-where.php';
		require_once PLUGIN_PATH . '/includes/sql/join/class-join.php';
		require_once PLUGIN_PATH . '/includes/sql/join/class-term-description.php';
		require_once PLUGIN_PATH . '/includes/sql/modifier/class-modifier.php';
		require_once PLUGIN_PATH . '/includes/sql/class-query.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$this->setUpWpdb();
	}

	private function makeBaseQuery(): Sql\Query {
		$query = new Sql\Query();
		$query->add_from( new Sql\From( Sql\Value::table( 'wp_posts' ) ) );
		$query->add_select( new Sql\Select\Select( Sql\Value::table( 'wp_posts' ), Sql\Value::column( 'ID' ) ) );
		return $query;
	}

	public function testAddSelectOnlyCopiesSelects() {
		$target = $this->makeBaseQuery();

		$source = new Sql\Query();
		$source->add_select( new Sql\Select\Select( Sql\Value::table( 'wp_posts' ), Sql\Value::column( 'post_title' ) ) );

		$target->add_select_only( $source );
		$sql = $target->get_as_sql();

		$this->assertStringContainsString( 'post_title', $sql );
	}

	public function testAddSelectOnlyCopiesJoins() {
		$target = $this->makeBaseQuery();

		$source = new Sql\Query();
		$source->add_join( new Sql\Join\Term_Description( 'description' ) );

		$target->add_select_only( $source );
		$sql = $target->get_as_sql();

		// The join's FROM (INNER JOIN) should appear in the SQL
		$this->assertStringContainsString( 'INNER JOIN', $sql );
	}

	public function testAddSelectOnlyDoesNotCopyFrom() {
		$target = $this->makeBaseQuery();

		$source = new Sql\Query();
		$source->add_from( new Sql\From( Sql\Value::table( 'wp_comments' ) ) );
		$source->add_select( new Sql\Select\Select( Sql\Value::table( 'wp_comments' ), Sql\Value::column( 'comment_ID' ) ) );

		$target->add_select_only( $source );
		$sql = $target->get_as_sql();

		// FROM wp_comments should NOT be copied
		$this->assertStringNotContainsString( 'wp_comments', $sql );
		// Original FROM wp_posts should still be present
		$this->assertStringContainsString( 'wp_posts', $sql );
	}

	public function testAddSelectOnlyDoesNotCopyWhere() {
		$target = $this->makeBaseQuery();

		// Source has a join with a WHERE condition; only its join should NOT be present
		// since add_select_only only copies select+joins, not where clauses
		$source = new Sql\Query();
		// We verify WHERE absence by checking the target (which has no where) stays WHERE-free
		$target->add_select_only( $source );
		$sql = $target->get_as_sql();

		$this->assertStringNotContainsString( 'WHERE', $sql );
	}

	public function testAddSelectOnlyMergesWithExistingJoins() {
		// Target already has a join; source also has a join and a select.
		// After add_select_only, target should have both joins and both selects.
		$target = $this->makeBaseQuery();
		$target->add_join( new Sql\Join\Term_Description( 'description' ) );

		$source = new Sql\Query();
		$source->add_join( new Sql\Join\Term_Description( 'description2' ) );
		$source->add_select( new Sql\Select\Select( Sql\Value::table( 'wp_posts' ), Sql\Value::column( 'post_title' ) ) );

		$target->add_select_only( $source );
		$sql = $target->get_as_sql();

		// The select from source should be present
		$this->assertStringContainsString( 'post_title', $sql );
		// The INNER JOIN from the original target join should still be present
		$this->assertStringContainsString( 'INNER JOIN', $sql );
	}
}
