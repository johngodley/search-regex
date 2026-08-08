<?php
/**
 * Unit tests for the Action\Action base class.
 *
 * @package Search_Regex
 */

use SearchRegex\Action\Type\Nothing;

class ActionTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	/**
	 * Search::get_row() can return `false` for a row that no longer matches the
	 * search conditions - for example after a single-row replace has removed the
	 * last occurrence of the search phrase. get_results() must not crash when it
	 * encounters this, it should just skip it.
	 */
	public function testFalseResultIsSkippedNotFatal() {
		$action = new Nothing();

		$results = $action->get_results( [ 'results' => [ false ] ] );

		$this->assertEquals( [], $results['results'] );
	}
}
