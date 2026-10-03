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

	private function grantCapabilities( array $granted, $has_legacy_filter = false, $priority = 10 ) {
		// has_filter() returns the callback priority, or false
		Functions\when( 'has_filter' )->justReturn( $has_legacy_filter ? $priority : false );
		Functions\when( 'current_user_can' )->alias(
			fn( $cap ) => in_array( $cap, $granted, true )
		);
	}

	public function testAdministratorHasEveryPermission() {
		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DEFAULT ] );

		foreach ( Plugin\Capabilities::get_every_capability() as $permission ) {
			$this->assertTrue( Plugin\Capabilities::has_access( $permission ), $permission );
		}
	}

	public function testDelegatedUserHasEveryPermissionExceptOptions() {
		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DELEGATED ] );

		$this->assertTrue( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH ) );
		$this->assertTrue( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_PRESETS ) );
		$this->assertTrue( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_SUPPORT ) );
		$this->assertFalse( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS ) );
	}

	public function testUserWithoutAccessHasNoPermissions() {
		$this->grantCapabilities( [] );

		foreach ( Plugin\Capabilities::get_every_capability() as $permission ) {
			$this->assertFalse( Plugin\Capabilities::has_access( $permission ), $permission );
		}
	}

	public function testLegacyFilterTakesFullPrecedenceOverDelegatedCapability() {
		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DELEGATED ], true );

		// Functions\expect('apply_filters') doesn't take effect here - it's shadowed by the
		// apply_filters stub the base TestCase installs in setUp(). Functions\when()->alias()
		// does override it, so branch on the hook name to cover both calls this code path makes
		// (the outer FILTER_CAPABILITY check, and the inner CAP_PLUGIN check inside
		// get_plugin_access(), which supplies the outer call's default/second argument).
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $default, ...$rest ) {
				if ( $hook === Plugin\Capabilities::FILTER_CAPABILITY ) {
					$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, $default );
					$this->assertSame( [ Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH ], $rest );

					return 'editor_only_capability';
				}

				return $default;
			}
		);

		$this->assertFalse( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH ) );
	}

	public function testLegacyFilterAtPriorityZeroIsUsed() {
		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DELEGATED ], true, 0 );

		Functions\when( 'apply_filters' )->alias(
			fn( $hook, $default ) => $hook === Plugin\Capabilities::FILTER_CAPABILITY ? Plugin\Capabilities::CAP_DELEGATED : $default
		);

		$this->assertTrue( Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS ) );
		$this->assertTrue( Plugin\Capabilities::is_administrator() );
		$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );
	}

	public function testMenuCapability() {
		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::CAP_DELEGATED ] );
		$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );

		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DELEGATED ] );
		$this->assertSame( Plugin\Capabilities::CAP_DELEGATED, Plugin\Capabilities::get_menu_capability() );

		$this->grantCapabilities( [ Plugin\Capabilities::CAP_DELEGATED ], true );
		$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );

		$this->grantCapabilities( [] );
		$this->assertSame( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );
	}

	public function testGetEveryCapabilityReturnsPermissionNames() {
		$this->assertEquals(
			[
				'searchregex_cap_manage',
				'searchregex_cap_options',
				'searchregex_cap_support',
				'searchregex_cap_preset',
			],
			Plugin\Capabilities::get_every_capability()
		);
	}
}
