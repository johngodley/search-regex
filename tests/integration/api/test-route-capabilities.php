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
			'presets_write' => [ 'preset', [ 'name' => 'Test', 'search' => [ 'source' => [ 'posts' ] ] ], 'POST' ],
		];
	}

	private function assertRouteAccess( $allowed ) {
		foreach ( $this->get_route_families() as $name => $route ) {
			$result = $this->callApi( $route[0], $route[1], $route[2] );

			if ( in_array( $name, $allowed, true ) ) {
				$this->assertEquals( 200, $result->status, $name . ': ' . wp_json_encode( $result->data ) );
			} else {
				$this->assertEquals( 403, $result->status, $name . ': ' . wp_json_encode( $result->data ) );
			}
		}
	}

	public function testDelegatedUserAccessesEverythingExceptSettings() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_DELEGATED );

		$this->assertRouteAccess( [ 'search', 'source', 'plugin', 'presets_read', 'presets_write' ] );
	}

	public function testUserWithoutAccessCannotAccessAnyRoute() {
		$this->setEditor();

		$this->assertRouteAccess( [] );
	}

	public function testAdministratorCanAccessEveryRouteFamily() {
		$this->setNonce();

		$this->assertRouteAccess( array_keys( $this->get_route_families() ) );
	}

	public function testLegacyManageFilterStillGrantsManageRoutes() {
		$this->setEditor();
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'grantLegacyManageToEditor' ], 10, 2 );

		$this->assertRouteAccess( [ 'search', 'source', 'plugin', 'presets_read', 'presets_write' ] );

		remove_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'grantLegacyManageToEditor' ], 10 );
	}

	public function testLegacyFilterTakesPrecedenceOverDelegatedCapability() {
		$this->setDelegatedUser( Plugin\Capabilities::CAP_DELEGATED );
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'keepDefaultCapability' ], 10, 2 );

		$this->assertRouteAccess( [] );

		remove_filter( Plugin\Capabilities::FILTER_CAPABILITY, [ $this, 'keepDefaultCapability' ], 10 );
	}

	public function grantLegacyManageToEditor( $capability, $permission_name ) {
		if ( $permission_name === Plugin\Capabilities::CAP_SEARCHREGEX_SEARCH ) {
			return 'editor';
		}

		return $capability;
	}

	public function keepDefaultCapability( $capability, $permission_name ) {
		return $capability;
	}
}
