<?php

use SearchRegex\Plugin;
use SearchRegex\Source;

class SensitiveSourcePermissionsApiTest extends SearchRegex_Api_Test {
	private function setDelegatedUser() {
		$this->setEditor();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_DELEGATED );
	}

	public function testDelegatedUserCanStillSearchOrdinarySources() {
		$this->setDelegatedUser();

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
			],
			'POST'
		);

		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );
	}

	public function testDelegatedUserCannotAccessUserSources() {
		$this->setDelegatedUser();

		$requests = [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts', 'user-meta' ] ], 'POST' ],
			[ 'source/user/complete/user_email', [ 'value' => 'example' ], 'GET' ],
			[ 'source/user/row/1', [], 'GET' ],
			[ 'source/user/row/1', [ 'replacement' => [ 'column' => 'display_name' ] ], 'POST' ],
			[ 'source/user/row/1/delete', [], 'POST' ],
			[ 'source/user-meta/row/1', [], 'GET' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( 403, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testDelegatedUserCannotAccessOptions() {
		$this->setDelegatedUser();

		$requests = [
			[ 'search', [ 'source' => [ 'options' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'options' ], 'action' => 'replace', 'save' => false ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts', 'options' ] ], 'POST' ],
			[ 'source/options/complete/option_name', [ 'value' => 'example' ], 'GET' ],
			[ 'source/options/row/1', [], 'GET' ],
			[ 'source/options/row/1', [ 'replacement' => [ 'column' => 'option_value' ] ], 'POST' ],
			[ 'source/options/row/1/delete', [], 'POST' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( 403, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testDelegatedUserCannotRunActionHooks() {
		$this->setDelegatedUser();

		foreach ( [ true, 'true', '1' ] as $save ) {
			$result = $this->callApi(
				'search',
				[
					'source' => [ 'posts' ],
					'action' => 'action',
					'actionOption' => [ 'hook' => 'init' ],
					'save' => $save,
				],
				'POST'
			);

			$this->assertEquals( 403, $result->status, wp_json_encode( $result->data ) );
		}

		foreach ( [ false, 'false', '0' ] as $save ) {
			$preview = $this->callApi(
				'search',
				[
					'source' => [ 'posts' ],
					'action' => 'action',
					'actionOption' => [ 'hook' => 'init' ],
					'save' => $save,
				],
				'POST'
			);

			$this->assertEquals( 200, $preview->status, wp_json_encode( $preview->data ) );
		}
	}

	private function countActionHookCalls( $save ) {
		$calls = 0;
		$counter = function () use ( &$calls ) {
			$calls++;
		};
		add_action( 'searchregex_test_hook', $counter );

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'posts' ],
				'action' => 'action',
				'actionOption' => [ 'hook' => 'searchregex_test_hook' ],
				'save' => $save,
			],
			'POST'
		);

		remove_action( 'searchregex_test_hook', $counter );
		$this->assertEquals( 200, $result->status, wp_json_encode( $result->data ) );

		return $calls;
	}

	public function testActionHookOnlyRunsWhenSaving() {
		$this->setNonce();
		$this->factory->post->create( [ 'post_title' => 'hook target' ] );

		// Positive control - saving runs the hook, so a zero count below is meaningful
		$this->assertGreaterThan( 0, $this->countActionHookCalls( true ) );

		foreach ( [ false, 'false', '0' ] as $save ) {
			$this->assertSame( 0, $this->countActionHookCalls( $save ), wp_json_encode( $save ) );
		}
	}

	public function testAdministratorCanAccessSensitiveSources() {
		global $wpdb;

		$this->setNonce();
		$option_id = $wpdb->get_var( "SELECT option_id FROM {$wpdb->options} WHERE option_name='blogname'" );

		$requests = [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'user-meta' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'options' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts' ], 'action' => 'action', 'actionOption' => [ 'hook' => 'init' ], 'save' => false ], 'POST' ],
			[ 'source/options/complete/option_name', [ 'value' => 'blog' ], 'GET' ],
			[ 'source/options/row/' . $option_id, [], 'GET' ],
			[ 'source/user/row/' . get_current_user_id(), [], 'GET' ],
		];

		foreach ( $requests as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( 200, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	private function getSensitiveRequests() {
		return [
			[ 'search', [ 'source' => [ 'user' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'options' ] ], 'POST' ],
			[ 'search', [ 'source' => [ 'posts' ], 'action' => 'action', 'actionOption' => [ 'hook' => 'searchregex_test_hook' ], 'save' => true ], 'POST' ],
		];
	}

	private function assertSensitiveAccess( $expected ) {
		foreach ( $this->getSensitiveRequests() as $request ) {
			$result = $this->callApi( $request[0], $request[1], $request[2] );

			$this->assertEquals( $expected, $result->status, $request[0] . ': ' . wp_json_encode( $result->data ) );
		}
	}

	public function testPluginRoleFilterGrantsSensitiveAccess() {
		add_filter( Plugin\Capabilities::CAP_PLUGIN, fn() => 'edit_pages' );
		$this->setEditor();

		$this->assertSensitiveAccess( 200 );
	}

	public function testLegacyFilterGrantsSensitiveAccess() {
		add_filter( Plugin\Capabilities::FILTER_CAPABILITY, fn() => 'edit_pages', 10, 2 );
		$this->setEditor();

		$this->assertSensitiveAccess( 200 );
	}

	public function testManageOptionsIsNotEnoughWhenPluginRoleIsStricter() {
		add_filter( Plugin\Capabilities::CAP_PLUGIN, fn() => 'searchregex_strict_access' );
		$this->setNonce();
		wp_get_current_user()->add_cap( Plugin\Capabilities::CAP_DELEGATED );

		$this->assertTrue( current_user_can( 'manage_options' ) );
		$this->assertSensitiveAccess( 403 );
	}

	public function testSensitiveSourceAliasesCannotBypassAdminAccess() {
		add_filter( 'searchregex_sources_core', [ $this, 'addUserAlias' ] );
		$this->setDelegatedUser();

		$result = $this->callApi(
			'search',
			[
				'source' => [ 'people-alias' ],
			],
			'POST'
		);

		$this->assertEquals( 403, $result->status, wp_json_encode( $result->data ) );
		remove_filter( 'searchregex_sources_core', [ $this, 'addUserAlias' ] );
	}

	public function addUserAlias( $sources ) {
		$sources[] = [
			'name' => 'people-alias',
			'class' => Source\Core\User::class,
			'label' => 'People Alias',
			'type' => 'core',
		];

		return $sources;
	}
}
