<?php
/**
 * Unit tests for the Plugin\Capabilities class.
 *
 * @package Search_Regex
 */

use Brain\Monkey\Functions;
use SearchRegex\Plugin;

class CapabilitiesTest extends TestCase {
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once PLUGIN_PATH . '/search-regex-loader.php';
	}

	public function testFallsBackToBaseAccessWhenNothingGranted() {
		Functions\expect( 'has_filter' )
			->once()
			->with( Plugin\Capabilities::FILTER_CAPABILITY )
			->andReturn( false );

		Functions\expect( 'current_user_can' )
			->once()
			->with( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS )
			->andReturn( false );

		Functions\expect( 'current_user_can' )
			->once()
			->with( Plugin\Capabilities::CAP_DEFAULT )
			->andReturn( true );

		$this->assertTrue(
			Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS )
		);
	}

	public function testDeniesAccessWhenNothingGrantedAndNoBaseAccess() {
		Functions\expect( 'has_filter' )
			->once()
			->with( Plugin\Capabilities::FILTER_CAPABILITY )
			->andReturn( false );

		Functions\expect( 'current_user_can' )
			->once()
			->with( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS )
			->andReturn( false );

		Functions\expect( 'current_user_can' )
			->once()
			->with( Plugin\Capabilities::CAP_DEFAULT )
			->andReturn( false );

		$this->assertFalse(
			Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS )
		);
	}

	public function testExplicitGrantPassesWithoutNeedingBaseAccess() {
		Functions\expect( 'has_filter' )
			->once()
			->with( Plugin\Capabilities::FILTER_CAPABILITY )
			->andReturn( false );

		// Granted directly, e.g. by a role/permission-management plugin - the base-access
		// fallback must not even be checked.
		Functions\expect( 'current_user_can' )
			->once()
			->with( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS )
			->andReturn( true );

		$this->assertTrue(
			Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS )
		);
	}

	public function testLegacyFilterTakesFullPrecedenceOverExplicitGrant() {
		Functions\expect( 'has_filter' )
			->once()
			->with( Plugin\Capabilities::FILTER_CAPABILITY )
			->andReturn( true );

		// Functions\expect('apply_filters') doesn't take effect here - it's shadowed by the
		// apply_filters stub the base TestCase installs in setUp(). Functions\when()->alias()
		// does override it, so branch on the hook name to cover both calls this code path makes
		// (the outer FILTER_CAPABILITY check, and the inner CAP_PLUGIN check inside
		// get_plugin_access(), which supplies the outer call's default/second argument).
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $default, ...$rest ) {
				if ( $hook === Plugin\Capabilities::FILTER_CAPABILITY ) {
					// Assert the legacy filter receives the correct arguments:
					// - $default should be the result of get_plugin_access() (which is CAP_DEFAULT)
					// - $rest[0] should be the legacy pseudo-capability name, not the new real capability
					$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, $default );
					$this->assertSame( [ Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS ], $rest );

					return 'editor_only_capability';
				}

				return $default;
			}
		);

		Functions\expect( 'current_user_can' )
			->once()
			->with( 'editor_only_capability' )
			->andReturn( false );

		// If the code regressed and fell through to checking the real capability directly
		// despite the legacy filter being registered, this test fails - no expectation is
		// set up for current_user_can( CAP_SEARCHREGEX_OPTIONS ), so Brain\Monkey raises on
		// the unmatched call.
		$this->assertFalse(
			Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS )
		);
	}

	public function testGetEveryCapabilityReturnsRealCapabilityNames() {
		$this->assertEquals(
			[
				'search_regex_manage',
				'search_regex_options',
				'search_regex_support',
				'search_regex_presets',
			],
			Plugin\Capabilities::get_every_capability()
		);
	}
}
