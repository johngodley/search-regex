<?php

use SearchRegex\Plugin;
use SearchRegex\Admin;

class SearchRegexAdminCapabilitiesTest extends SearchRegex_Api_Test {
	private $legacy_names = [];

	private function setDelegatedUser( $capability ) {
		$this->setEditor();
		wp_get_current_user()->add_cap( $capability );
	}

	private function getPreloadData() {
		if ( ! class_exists( Admin\Admin::class ) ) {
			require_once dirname( SEARCHREGEX_FILE ) . '/includes/admin/class-admin.php';
		}

		$method = new ReflectionMethod( Admin\Admin::class, 'get_preload_data' );
		$method->setAccessible( true );

		return $method->invoke( Admin\Admin::init() );
	}

	public function testAdministratorMenuUsesBaseAccess() {
		$this->setNonce();

		$this->assertEquals( Plugin\Capabilities::CAP_DEFAULT, Plugin\Capabilities::get_menu_capability() );
	}

	public function testUserWithoutPluginAccessCannotSeeMenu() {
		$this->setEditor();

		$this->assertFalse( current_user_can( Plugin\Capabilities::get_menu_capability() ) );
	}

	public function testDelegatedUserCanSeeMenu() {
		foreach ( Plugin\Capabilities::get_every_capability() as $capability ) {
			$this->setDelegatedUser( $capability );

			$this->assertEquals( $capability, Plugin\Capabilities::get_menu_capability() );
			$this->assertTrue( current_user_can( Plugin\Capabilities::get_menu_capability() ) );
		}
	}

	public function testDelegatedUserOnlyGetsTheirPages() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS );

		$this->assertEquals( [ 'options' ], Plugin\Capabilities::get_available_pages() );
	}

	public function testHasAccessDerivesLegacyNameWhenOmitted() {
		$this->setNonce();
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'recordLegacyName' ], 10, 2 );

		Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS );
		Plugin\Capabilities::has_access( Plugin\Capabilities::CAP_SEARCHREGEX_PRESETS );

		remove_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'recordLegacyName' ], 10 );

		$this->assertEquals(
			[ Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_PRESETS ],
			$this->legacy_names
		);
	}

	public function recordLegacyName( $capability, $permission_name ) {
		$this->legacy_names[] = $permission_name;

		return $capability;
	}

	public function testPreloadDataIsEmptyWithoutSearchOrPresetAccess() {
		foreach ( [ Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS, Plugin\Capabilities::CAP_SEARCHREGEX_SUPPORT ] as $capability ) {
			$this->setDelegatedUser( $capability );

			$preload = $this->getPreloadData();

			$this->assertEquals( [], $preload['sources'], $capability );
			$this->assertEquals( [], $preload['presets'], $capability );
			$this->assertEquals( [], $preload['schema'], $capability );
		}
	}

	public function testPreloadDataIncludesSourcesWithSearchAccess() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH );

		$this->assertNotEmpty( $this->getPreloadData()['sources'] );
	}
}
