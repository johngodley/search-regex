<?php

use SearchRegex\Plugin;

class SearchRegexApiRouteCapabilitiesTest extends SearchRegex_Api_Test {
	private function setDelegatedUser( $capability ) {
		$this->setEditor();
		wp_get_current_user()->add_cap( $capability );
	}

	private function get_route_families() {
		return [
			'search' => [ 'search', [ 'source' => [ 'posts' ] ], 'POST' ],
			'source' => [ 'source', [], 'GET' ],
			'plugin' => [ 'plugin/test', [], 'GET' ],
			'settings_read' => [ 'setting', [], 'GET' ],
			'settings_write' => [ 'setting', [], 'POST' ],
			'presets_read' => [ 'preset', [], 'GET' ],
			'presets_write' => [ 'preset/import', [], 'POST' ],
		];
	}

	private function assertRouteAccess( $allowed ) {
		foreach ( $this->get_route_families() as $name => $route ) {
			$result = $this->callApi( $route[0], $route[1], $route[2] );

			if ( in_array( $name, $allowed, true ) ) {
				$this->assertNotEquals( 403, $result->status, $name . ': ' . wp_json_encode( $result->data ) );
			} else {
				$this->assertEquals( 403, $result->status, $name . ': ' . wp_json_encode( $result->data ) );
			}
		}
	}

	public function testSearchCapabilityOnlyAccessesSearchRoutes() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH );

		$this->assertRouteAccess( [ 'search', 'source', 'plugin' ] );
	}

	public function testOptionsCapabilityOnlyAccessesSettingsRoutes() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_OPTIONS );

		$this->assertRouteAccess( [ 'settings_read', 'settings_write' ] );
	}

	public function testPresetsCapabilityOnlyAccessesPresetRoutes() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_PRESETS );

		$this->assertRouteAccess( [ 'presets_read', 'presets_write' ] );
	}

	public function testSupportCapabilityDoesNotGrantDataRouteAccess() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_SEARCHREGEX_SUPPORT );

		$this->assertRouteAccess( [] );
	}

	public function testAdministratorCanAccessEveryRouteFamily() {
		$this->setNonce();

		$this->assertRouteAccess( array_keys( $this->get_route_families() ) );
	}

	public function testLegacyManageFilterStillGrantsEveryRestRoute() {
		$this->setEditor();
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'grantLegacyManageToEditor' ], 10, 2 );

		$this->assertRouteAccess( array_keys( $this->get_route_families() ) );

		remove_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'grantLegacyManageToEditor' ], 10 );
	}

	public function grantLegacyManageToEditor( $capability, $permission_name ) {
		if ( $permission_name === Plugin\Capabilities::LEGACY_CAP_SEARCHREGEX_SEARCH ) {
			return 'editor';
		}

		return $capability;
	}
}
